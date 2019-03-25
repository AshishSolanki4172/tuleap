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
import localVue from "../../../support/local-vue.js";
import ArtifactsComparison from "./ArtifactsComparison.vue";
import { create } from "../../../support/factories";

describe("ArtifactsComparison", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(ArtifactsComparison, {
            localVue,
            propsData: {
                reference_artifacts: [],
                compared_artifacts: [],
                current_depth: 1
            }
        });
    });

    describe("#artifact_comparisons", () => {
        const reference = create("artifact", { id: 1, description: "old description" });
        const compared_to = create("artifact", { id: 1, description: "new description" });

        beforeEach(() => {
            wrapper.setProps({
                reference_artifacts: [reference],
                compared_artifacts: [compared_to]
            });
        });

        it("returns comparisons", () => {
            expect(wrapper.vm.artifact_comparisons).toEqual([{ reference, compared_to }]);
        });

        describe("when artifact removed", () => {
            beforeEach(() => {
                wrapper.setProps({
                    reference_artifacts: [create("artifact")],
                    compared_artifacts: []
                });
            });

            it("does not returns comparison", () => {
                expect(wrapper.vm.artifact_comparisons).toEqual([]);
            });
        });
    });

    describe("#added_artifacts", () => {
        const added_artifact = create("artifact");

        beforeEach(() => {
            wrapper.setProps({ reference_artifacts: [], compared_artifacts: [added_artifact] });
        });

        it("returns added artifact", () => {
            expect(wrapper.vm.added_artifacts).toEqual([added_artifact]);
        });
    });

    describe("#removed_artifacts", () => {
        const removed_artifact = create("artifact");

        beforeEach(() => {
            wrapper.setProps({ reference_artifacts: [removed_artifact], compared_artifacts: [] });
        });

        it("returns removed artifact", () => {
            expect(wrapper.vm.removed_artifacts).toEqual([removed_artifact]);
        });
    });
});
