/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import { describe, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import WidgetModalEditTime from "./WidgetModalEditTime.vue";
import { createLocalVueForTests } from "../../helpers/local-vue.js";
import type { Artifact, PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";

vi.mock("tlp", () => {
    return { datePicker: vi.fn() };
});

describe("Given a personal timetracking widget modal", () => {
    const current_artifact = { id: 10 } as Artifact;

    async function getWrapperInstance(
        time_data?: PersonalTime,
    ): Promise<Wrapper<WidgetModalEditTime>> {
        const component_options = {
            localVue: await createLocalVueForTests(),
            propsData: {
                timeData: time_data,
                artifact: current_artifact,
            },
        };
        return shallowMount(WidgetModalEditTime, component_options);
    }

    describe("Initialisation", () => {
        it("When no date is given, then it should be initialized", async () => {
            const wrapper = await getWrapperInstance();
            expect(wrapper.vm.date).toBeDefined();
        });

        it("When a date is given, then it should use it", async () => {
            const date = "2023-10-30";
            const wrapper = await getWrapperInstance({ date } as PersonalTime);

            expect(wrapper.vm.date).toBe(date);
        });
    });

    describe("Submit", () => {
        it("Given a new time is not filled, then the time is invalid", async () => {
            const wrapper = await getWrapperInstance({} as PersonalTime);
            wrapper.find("[data-test=timetracking-submit-time]").trigger("click");

            expect(wrapper.vm.error_message).toBe("Time is required");
        });

        it("Given a new time is submitted with an incorrect format, then the time is invalid", async () => {
            const wrapper = await getWrapperInstance({ minutes: "0a" } as PersonalTime);
            wrapper.find("[data-test=timetracking-submit-time]").trigger("click");

            expect(wrapper.vm.error_message).toBe("Please check time's format (hh:mm)");
        });

        it("Given a new time is submitted, then the submit button is disabled and a new event is sent", async () => {
            const time = {
                date: "2020-04-03",
                minutes: 10,
            } as PersonalTime;
            const wrapper = await getWrapperInstance(time);

            wrapper.find("[data-test=timetracking-submit-time]").trigger("click");

            expect(wrapper.emitted("validate-time")).toStrictEqual([
                ["2020-04-03", 10, "00:10", ""],
            ]);
            expect(wrapper.vm.is_loading).toBe(true);
        });
    });
});
