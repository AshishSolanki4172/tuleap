/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueGettextProvider } from "../../vue-gettext-provider";
import type { BacklogItem, Campaign, GlobalExportProperties } from "../../../type";
import type {
    TrackerStructure,
    DateTimeLocaleInformation,
    ArtifactResponse,
    ArtifactFromReport,
} from "@tuleap/plugin-docgen-docx";
import {
    formatArtifact,
    getArtifacts,
    getTestManagementExecution,
    memoize,
    retrieveArtifactsStructure,
    retrieveTrackerStructure,
} from "@tuleap/plugin-docgen-docx";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import { getTraceabilityMatrix } from "@tuleap/plugin-testmanagement-app/src/helpers/ExportAsDocument/Reporter/traceability-matrix-creator";
import { getExecutionsForCampaigns } from "@tuleap/plugin-testmanagement-app/src/helpers/ExportAsDocument/Reporter/executions-for-campaigns-retriever";
import {
    buildStepDefinitionEnhancedWithResultsFunction,
    buildStepDefinitionFunction,
} from "@tuleap/plugin-testmanagement-app/src/helpers/ExportAsDocument/Reporter/step-test-definition-formatter";
import type {
    ExportDocument,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
    LastExecutionsMap,
} from "@tuleap/plugin-testmanagement-app/src/type";
import { getLastExecutionForTest } from "@tuleap/plugin-testmanagement-app/src/helpers/ExportAsDocument/Reporter/last-executions-retriever";

interface TrackerStructurePromiseTuple {
    readonly tracker_id: number;
    readonly tracker_structure_promise: Promise<TrackerStructure>;
}

export async function createExportReport(
    gettext_provider: VueGettextProvider,
    global_properties: GlobalExportProperties,
    backlog_items: ReadonlyArray<BacklogItem>,
    campaigns: ReadonlyArray<Campaign>,
    datetime_locale_information: DateTimeLocaleInformation,
): Promise<ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>> {
    const get_test_execution = memoize(getTestManagementExecution);

    const tracker_structure_promises_map: Map<number, TrackerStructurePromiseTuple> = new Map();
    if (global_properties.testdefinition_tracker_id !== null) {
        tracker_structure_promises_map.set(global_properties.testdefinition_tracker_id, {
            tracker_id: global_properties.testdefinition_tracker_id,
            tracker_structure_promise: retrieveTrackerStructure(
                global_properties.testdefinition_tracker_id,
            ),
        });
    }

    const backlog_item_artifact_ids: Set<number> = new Set();
    for (const item of backlog_items) {
        backlog_item_artifact_ids.add(item.artifact.id);

        const tracker_id = item.artifact.tracker.id;
        if (tracker_structure_promises_map.has(tracker_id)) {
            continue;
        }
        tracker_structure_promises_map.set(tracker_id, {
            tracker_id,
            tracker_structure_promise: retrieveTrackerStructure(tracker_id),
        });
    }

    const tracker_structure_map: Map<number, TrackerStructure> = new Map();
    await limitConcurrencyPool(
        4,
        [...tracker_structure_promises_map.values()],
        async (tracker_structure_tuple: TrackerStructurePromiseTuple): Promise<void> => {
            tracker_structure_map.set(
                tracker_structure_tuple.tracker_id,
                await tracker_structure_tuple.tracker_structure_promise,
            );
        },
    );

    const executions_map = await getExecutionsForCampaigns(campaigns);
    const test_def_artifact_ids: Set<number> = new Set();
    if (global_properties.testdefinition_tracker_id !== null) {
        for (const { executions } of executions_map.values()) {
            for (const exec of executions) {
                test_def_artifact_ids.add(exec.definition.id);
            }
        }
    }

    const test_def_artifact_ids_without_execution: Set<number> = new Set();
    const test_def_artifacts_with_execution: Map<number, ArtifactResponse> = new Map();
    const last_executions_map: LastExecutionsMap = new Map();
    //filter to get only last execution and associated artifact definition
    for (const test_definition_id of test_def_artifact_ids.values()) {
        const last_execution = getLastExecutionForTest(test_definition_id, executions_map);

        if (last_execution !== null) {
            last_executions_map.set(test_definition_id, last_execution);

            test_def_artifacts_with_execution.set(
                last_execution.definition.artifact.id,
                last_execution.definition.artifact,
            );
        } else {
            test_def_artifact_ids_without_execution.add(test_definition_id);
        }
    }

    const all_artifacts: ArtifactResponse[] = [
        ...(
            await getArtifacts(
                new Set([...backlog_item_artifact_ids, ...test_def_artifact_ids_without_execution]),
            )
        ).values(),
        ...test_def_artifacts_with_execution.values(),
    ];
    const all_artifacts_structures: ReadonlyArray<ArtifactFromReport> =
        await retrieveArtifactsStructure(tracker_structure_map, all_artifacts, get_test_execution);

    return {
        name: gettext_provider.interpolate(
            gettext_provider.$gettext("Test Report %{ milestone_title }"),
            { milestone_title: global_properties.milestone_name },
        ),
        backlog: all_artifacts_structures
            .filter((artifact) => backlog_item_artifact_ids.has(artifact.id))
            .map((artifact) =>
                formatArtifact(
                    artifact,
                    datetime_locale_information,
                    global_properties.base_url,
                    global_properties.artifact_links_types,
                    buildStepDefinitionFunction(),
                ),
            ),
        traceability_matrix: getTraceabilityMatrix(executions_map, datetime_locale_information),
        tests: all_artifacts_structures
            .filter((artifact) => test_def_artifact_ids.has(artifact.id))
            .map((artifact) =>
                formatArtifact(
                    artifact,
                    datetime_locale_information,
                    global_properties.base_url,
                    global_properties.artifact_links_types,
                    buildStepDefinitionEnhancedWithResultsFunction(
                        artifact,
                        last_executions_map.get(artifact.id) ?? null,
                    ),
                ),
            ),
    };
}
