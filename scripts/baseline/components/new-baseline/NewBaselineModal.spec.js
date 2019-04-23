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

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import { restore, rewire$getOpenMilestones, rewire$createBaseline } from "../../api/rest-querier";
import NewBaselineModal from "./NewBaselineModal.vue";
import MilestonesSelect from "./MilestonesSelect.vue";
import MilestonesSelectSkeleton from "./MilestonesSelectSkeleton.vue";
import { create } from "../../support/factories";
import store_options from "../../store/options.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper";

describe("NewBaselineModal", () => {
    const error_message_selector = '[data-test-type="error-message"]';
    const information_message_selector = '[data-test-type="information_message"]';
    const spinner_selector = '[data-test-type="spinner"]';
    const cancel_selector = '[data-test-action="cancel"]';
    const submit_selector = '[data-test-action="submit"]';

    let getOpenMilestones;
    let createBaseline;
    let $store;
    let wrapper;

    const a_milestone = create("milestone");
    const a_baseline = create("baseline");

    let getOpenMilestonesResolve;
    let getOpenMilestonesReject;

    beforeEach(async () => {
        getOpenMilestones = jasmine.createSpy("getOpenMilestones");
        getOpenMilestones.and.returnValue(
            new Promise((resolve, reject) => {
                getOpenMilestonesResolve = resolve;
                getOpenMilestonesReject = reject;
            })
        );
        rewire$getOpenMilestones(getOpenMilestones);

        createBaseline = jasmine.createSpy("createBaseline");
        createBaseline.and.returnValue(Promise.resolve(a_baseline));
        rewire$createBaseline(createBaseline);

        $store = createStoreMock(store_options);

        wrapper = shallowMount(NewBaselineModal, {
            propsData: { project_id: 1 },
            localVue,
            mocks: {
                $store
            }
        });
        await wrapper.vm.$nextTick();
    });

    afterEach(restore);

    it("shows skeleton", () => {
        expect(wrapper.contains(MilestonesSelectSkeleton)).toBeTruthy();
    });

    describe("when getOpenMilestones() fail", () => {
        beforeEach(async () => {
            getOpenMilestonesReject("rejection");
            await Vue.nextTick();
        });

        it("shows error message", () => {
            expect(wrapper.contains(error_message_selector)).toBeTruthy();
        });

        it("shows information message", () => {
            expect(wrapper.contains(information_message_selector)).toBeTruthy();
        });

        it("does not show skeleton", () => {
            expect(wrapper.contains(MilestonesSelectSkeleton)).toBeFalsy();
        });
    });

    describe("when getOpenMilestones() is successful", () => {
        beforeEach(async () => {
            getOpenMilestonesResolve([a_milestone]);
            await Vue.nextTick();
        });

        it("does not show error message", () => {
            expect(wrapper.contains(error_message_selector)).toBeFalsy();
        });

        it("does not show skeleton", () => {
            expect(wrapper.contains(MilestonesSelectSkeleton)).toBeFalsy();
        });

        it("shows a list of milestones", () => {
            expect(wrapper.contains(MilestonesSelect)).toBeTruthy();
        });

        it("passes milestones returned by getOpenMilestones() to MilestoneList", () => {
            expect(wrapper.find(MilestonesSelect).props().milestones).toEqual([a_milestone]);
        });
    });

    describe("saveBaseline()", () => {
        let createBaselineResolve;
        let createBaselineReject;

        beforeEach(() => {
            createBaseline.and.returnValue(
                new Promise((resolve, reject) => {
                    createBaselineResolve = resolve;
                    createBaselineReject = reject;
                })
            );
            wrapper.vm.saveBaseline();
        });

        it("shows spinner", () => {
            expect(wrapper.contains(spinner_selector)).toBeTruthy();
        });

        it("disables buttons", () => {
            expect(wrapper.find(cancel_selector).attributes("disabled")).toEqual("disabled");
            expect(wrapper.find(submit_selector).attributes("disabled")).toEqual("disabled");
        });

        describe("when createBaseline() fail", () => {
            beforeEach(async () => {
                createBaselineReject("rejection");
                await Vue.nextTick();
            });

            it("shows an error message", () => {
                expect(wrapper.find(error_message_selector).text).not.toBe(null);
            });
            it("enables cancel buttons", () => {
                expect(wrapper.find(cancel_selector).attributes("disabled")).not.toEqual(
                    "disabled"
                );
            });
        });

        describe("when createBaseline() is successful", () => {
            beforeEach(async () => {
                createBaselineResolve(a_baseline);
                await Vue.nextTick();
            });

            it("notify user with successful creation", () => {
                expect($store.commit).toHaveBeenCalledWith(
                    "dialog_interface/notify",
                    jasmine.any(Object)
                );
            });
            it("reloads all baselines", () => {
                expect($store.dispatch).toHaveBeenCalledWith("baselines/load", { project_id: 1 });
            });
            it("hides modal", () => {
                expect($store.commit).toHaveBeenCalledWith("dialog_interface/hideModal");
            });
        });
    });
});
