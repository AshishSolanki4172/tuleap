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
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import DeleteConfirmationModal from "./DeleteConfirmationModal.vue";

describe("DeleteConfirmationModal", () => {
    const confirm_selector = '[data-test-action="confirm"]';
    const spinner_selector = '[data-test-type="spinner"]';

    let confirm;
    let confirmResolve;

    let wrapper;

    beforeEach(() => {
        confirm = jasmine.createSpy("confirm");
        confirm.and.returnValue(
            new Promise(resolve => {
                confirmResolve = resolve;
            })
        );

        wrapper = shallowMount(DeleteConfirmationModal, {
            propsData: {
                submit_label: "Confirmation message",
                on_submit: confirm
            },
            localVue
        });
    });

    it("does not show spinner", () => {
        expect(wrapper.contains(spinner_selector)).toBeFalsy();
    });
    it("enables confirm button", () => {
        expect(wrapper.find(confirm_selector).attributes().disabled).toBeUndefined();
    });

    describe("when confirming", () => {
        beforeEach(async () => {
            wrapper.find(confirm_selector).trigger("click");
            await wrapper.vm.$nextTick();
        });

        it("shows spinner", () => {
            expect(wrapper.contains(spinner_selector)).toBeTruthy();
        });
        it("disables confirm button", () => {
            expect(wrapper.find(confirm_selector).attributes().disabled).toEqual("disabled");
        });
        it("calls confirm method", () => {
            expect(confirm).toHaveBeenCalled();
        });
    });

    describe("when deletion is completed ", () => {
        beforeEach(async () => {
            wrapper.find(confirm_selector).trigger("click");
            confirmResolve("resolved");
            await wrapper.vm.$nextTick();
        });

        it("does not show spinner any more", () => {
            expect(wrapper.contains(spinner_selector)).toBeFalsy();
        });
        it("enables confirm button", () => {
            expect(wrapper.find(confirm_selector).attributes().disabled).toBeUndefined();
        });
    });
});
