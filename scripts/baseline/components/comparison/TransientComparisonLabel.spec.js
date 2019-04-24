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
import TransientComparisonLabel from "./TransientComparisonLabel.vue";
import { createStoreMock } from "../../support/store-wrapper.spec-helper";
import store_options from "../../store/store_options";
import SaveComparisonModal from "./SaveComparisonModal.vue";

describe("TransientComparisonLabel", () => {
    const save_comparison_selector = '[data-test-action="save-comparison"]';
    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock(store_options);

        wrapper = shallowMount(TransientComparisonLabel, {
            propsData: {
                from_baseline_id: 1,
                to_baseline_id: 2
            },
            localVue,
            mocks: { $store }
        });
    });

    describe("when saving comparison", () => {
        beforeEach(() => wrapper.find(save_comparison_selector).trigger("click"));

        it("shows save comparison modal", () => {
            expect($store.commit).toHaveBeenCalledWith(
                "dialog_interface/showModal",
                jasmine.objectContaining({
                    component: SaveComparisonModal,
                    props: {
                        base_baseline_id: 1,
                        compared_to_baseline_id: 2
                    }
                })
            );
        });
    });
});
