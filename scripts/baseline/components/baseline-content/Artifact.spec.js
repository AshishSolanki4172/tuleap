/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import store_options from "../../store/store_options";
import { create, createList } from "../../support/factories";
import DepthLimitReachedMessage from "../common/DepthLimitReachedMessage.vue";
import Artifact from "./Artifact.vue";
import ArtifactsList from "./ArtifactsList.vue";

describe("Artifact", () => {
    const toggle_selector = '[data-test-action="toggle-expand-collapse"]';
    const artifact_fields_selector = '[data-test-type="artifact-fields"]';
    const artifact_description_selector = '[data-test-type="artifact-description"]';
    const artifact_status_selector = '[data-test-type="artifact-status"]';

    let isLimitReachedOnArtifact;

    const artifact_where_not_limit_reached = create("baseline_artifact");
    const artifact_where_limit_reached = create("baseline_artifact");

    let $store;
    let wrapper;

    beforeEach(() => {
        const linked_artifact = create("baseline_artifact", { title: "Story" });

        isLimitReachedOnArtifact = jasmine
            .createSpy("isLimitReachedOnArtifact")
            .and.returnValue(false)
            .withArgs(artifact_where_limit_reached)
            .and.returnValue(true);

        $store = createStoreMock({
            ...store_options,
            getters: {
                "semantics/field_label": () => "My description",
                "semantics/is_field_label_available": () => true,
                "current_baseline/findArtifactsByIds": () => [linked_artifact],
                "current_baseline/isLimitReachedOnArtifact": isLimitReachedOnArtifact,
                "current_baseline/filterArtifacts": () => []
            }
        });

        wrapper = shallowMount(Artifact, {
            propsData: {
                artifact: create("baseline_artifact", {
                    title: "Epic",
                    linked_artifact_ids: [linked_artifact.id]
                })
            },
            localVue,
            mocks: {
                $store
            }
        });
    });

    describe("when artifact has description", () => {
        beforeEach(() => {
            wrapper.setProps({
                artifact: create("baseline_artifact", {
                    description: "my description"
                })
            });
        });

        it("shows artifact descriptions", () => {
            expect(wrapper.contains(artifact_description_selector)).toBeTruthy();
        });
    });

    describe("when artifact has no description", () => {
        beforeEach(async () => {
            wrapper.setProps({
                artifact: create("baseline_artifact", { description: null })
            });
            await wrapper.vm.$nextTick();
        });

        it("does not show description", () => {
            expect(
                wrapper.find(artifact_fields_selector).contains(artifact_description_selector)
            ).toBeFalsy();
        });
    });

    describe("when artifact has status", () => {
        beforeEach(() => {
            wrapper.setProps({
                artifact: create("baseline_artifact", { status: "my status" })
            });
        });

        it("shows artifact status", () => {
            expect(wrapper.contains(artifact_status_selector)).toBeTruthy();
        });
    });

    describe("when artifact has no status", () => {
        beforeEach(async () => {
            wrapper.setProps({
                artifact: create("baseline_artifact", { status: null })
            });
            await wrapper.vm.$nextTick();
        });

        it("does not show status", () => {
            expect(
                wrapper.find(artifact_fields_selector).contains(artifact_status_selector)
            ).toBeFalsy();
        });
    });

    describe("when artifacts tree has reached depth limit", () => {
        beforeEach(() => wrapper.setProps({ artifact: artifact_where_limit_reached }));

        it("shows depth limit reached message", () => {
            expect(wrapper.contains(DepthLimitReachedMessage)).toBeTruthy();
        });

        it("does not show linked artifact", () => {
            expect(wrapper.contains(ArtifactsList)).toBeFalsy();
        });
    });

    describe("when artifacts tree has not reached depth limit", () => {
        beforeEach(() => wrapper.setProps({ artifact: artifact_where_not_limit_reached }));

        describe("when some linked artifacts are filtered", () => {
            const filtered_linked_artifacts = createList("baseline_artifact", 3);

            beforeEach(() =>
                ($store.getters["current_baseline/filterArtifacts"] = () =>
                    filtered_linked_artifacts));

            it("shows only visible linked artifacts", () => {
                expect(wrapper.contains(ArtifactsList)).toBeTruthy();
                expect(wrapper.find(ArtifactsList).props().artifacts).toEqual(
                    filtered_linked_artifacts
                );
            });
        });
    });

    describe("when toggle expand/collapse", () => {
        beforeEach(async () => {
            wrapper.find(toggle_selector).trigger("click");
            await wrapper.vm.$nextTick();
        });

        it("hides fields", () => {
            expect(wrapper.find(artifact_fields_selector).isVisible()).toBeFalsy();
        });

        describe("when toggle expand/collapse again", () => {
            beforeEach(async () => {
                wrapper.find(toggle_selector).trigger("click");
                await wrapper.vm.$nextTick();
            });

            it("shows fields", () => {
                expect(wrapper.find(artifact_fields_selector).isVisible()).toBeTruthy();
            });
        });
    });
});
