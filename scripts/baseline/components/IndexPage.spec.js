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
import localVue from "../support/local-vue.js";
import IndexPage from "./IndexPage.vue";
import router from "../router";
import { createStoreMock } from "../support/store-wrapper.spec-helper";
import store_options from "../store/store_options";
import { createList } from "../support/factories";

describe("IndexPage", () => {
    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock(store_options);

        wrapper = shallowMount(IndexPage, {
            propsData: { project_id: 1 },
            localVue,
            router,
            mocks: {
                $store
            }
        });
    });

    describe("when clicking on new baseline button", () => {
        beforeEach(() => wrapper.find('[data-test-action="new-baseline"]').trigger("click"));

        it("shows new modal", () => {
            expect($store.commit).toHaveBeenCalledWith(
                "dialog_interface/showModal",
                jasmine.any(Object)
            );
        });
    });

    describe("when some baselines are available", () => {
        beforeEach(() => {
            $store.state.baselines.baselines = createList("baseline", 2);
            $store.state.baselines.are_baselines_loading = false;
        });

        describe("when clicking on show comparison button", () => {
            beforeEach(() => wrapper.find('[data-test-action="show-comparison"]').trigger("click"));

            it("shows new modal", () => {
                expect($store.commit).toHaveBeenCalledWith(
                    "dialog_interface/showModal",
                    jasmine.any(Object)
                );
            });
        });
    });
});
