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

import { shallowMount } from "@vue/test-utils";
import GanttBoard from "./GanttBoard.vue";
import type { Task } from "../../type";
import GanttTask from "./Task/GanttTask.vue";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import TimePeriodControl from "./TimePeriod/TimePeriodControl.vue";
import { TimePeriodQuarter } from "../../helpers/time-period-quarter";

window.ResizeObserver =
    window.ResizeObserver ||
    jest.fn().mockImplementation(() => ({
        disconnect: jest.fn(),
        observe: jest.fn(),
        unobserve: jest.fn(),
    }));

describe("GanttBoard", () => {
    const windowResizeObserver = window.ResizeObserver;

    afterEach(() => {
        window.ResizeObserver = windowResizeObserver;
    });

    it("Displays all tasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
                tasks: [
                    { id: 1, dependencies: {} },
                    { id: 2, dependencies: {} },
                    { id: 3, dependencies: {} },
                ] as Task[],
                locale: "en_US",
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(3);
    });

    it("Displays months according to tasks", async () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
                tasks: [
                    { id: 1, start: new Date(2020, 3, 15), dependencies: {} },
                    { id: 2, start: new Date(2020, 3, 20), dependencies: {} },
                ] as Task[],
                locale: "en_US",
            },
        });

        wrapper.setData({
            now: new Date(2020, 3, 15),
        });
        await wrapper.vm.$nextTick();

        const time_period_header = wrapper.findComponent(TimePeriodHeader);
        expect(time_period_header.exists()).toBe(true);
        expect(
            time_period_header.props("time_period").units.map((month: Date) => month.toDateString())
        ).toStrictEqual(["Wed Apr 01 2020", "Fri May 01 2020"]);
        expect(time_period_header.props("nb_additional_units")).toBe(0);
    });

    it("Observes the resize of the time period", () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
                tasks: [
                    { id: 1, start: new Date(2020, 3, 15), dependencies: {} },
                    { id: 2, start: new Date(2020, 3, 20), dependencies: {} },
                ] as Task[],
                locale: "en_US",
            },
        });

        const time_period = wrapper.findComponent(TimePeriodHeader);
        expect(time_period.exists()).toBe(true);
        expect(observe).toHaveBeenCalledWith(time_period.element);
    });

    it("Fills the empty space of additional months if the user resize the viewport", async () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
                tasks: [
                    { id: 1, start: new Date(2020, 3, 15), dependencies: {} },
                    { id: 2, start: new Date(2020, 3, 20), dependencies: {} },
                ] as Task[],
                locale: "en_US",
            },
        });

        wrapper.setData({
            now: new Date(2020, 3, 15),
        });
        await wrapper.vm.$nextTick();

        const time_period_header = wrapper.findComponent(TimePeriodHeader);
        expect(time_period_header.exists()).toBe(true);
        expect(
            time_period_header.props("time_period").units.map((month: Date) => month.toDateString())
        ).toStrictEqual(["Wed Apr 01 2020", "Fri May 01 2020"]);
        expect(time_period_header.props("nb_additional_units")).toBe(0);

        const observerCallback = mockResizeObserver.mock.calls[0][0];
        await observerCallback([
            ({
                contentRect: { width: 450 } as DOMRectReadOnly,
                target: time_period_header.element,
            } as unknown) as ResizeObserverEntry,
        ]);

        expect(time_period_header.props("nb_additional_units")).toBe(2);
    });

    it("Use a different time period if user chose a different timescale", async () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                visible_natures: [],
                tasks: [
                    { id: 1, dependencies: {} },
                    { id: 2, dependencies: {} },
                    { id: 3, dependencies: {} },
                ] as Task[],
                locale: "en_US",
            },
        });

        expect(wrapper.findComponent(TimePeriodHeader).props("time_period")).toBeInstanceOf(
            TimePeriodMonth
        );
        await wrapper.findComponent(TimePeriodControl).vm.$emit("input", "quarter");
        expect(wrapper.findComponent(TimePeriodHeader).props("time_period")).toBeInstanceOf(
            TimePeriodQuarter
        );
    });
});
