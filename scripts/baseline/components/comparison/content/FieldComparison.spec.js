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
import FieldComparison from "./FieldComparison.vue";

describe("FieldComparison", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(FieldComparison, {
            localVue,
            propsData: {
                semantic: "description",
                tracker_id: 1,
                base: "My description",
                compared_to: "New description"
            }
        });
    });

    it("renders deletion", () => {
        expect(wrapper.findAll("del").length).toEqual(1);
        expect(wrapper.find("del").text()).toEqual("My");
    });

    it("renders addition", () => {
        expect(wrapper.findAll("ins").length).toEqual(1);
        expect(wrapper.find("ins").text()).toEqual("New");
    });

    describe("when compared values contain html", () => {
        beforeEach(() => {
            wrapper.setProps({
                base: "My description<div onload=alert('xss')>",
                compared_to: "<div onload=alert('xss')>My description"
            });
        });

        it("does not render dirty html", () => {
            expect(wrapper.html()).not.toContain("<div onload=alert('xss')>");
        });
    });

    describe("when compared values are numeric", () => {
        beforeEach(() => {
            wrapper.setProps({
                base: 3,
                compared_to: 5
            });
        });

        it("converts values to string", () => {
            expect(wrapper.find("del").text()).toEqual("3");
            expect(wrapper.find("ins").text()).toEqual("5");
        });
    });

    describe("when base value is null", () => {
        beforeEach(() => {
            wrapper.setProps({
                base: null,
                compared_to: 5
            });
        });

        it("does not show base value", () => {
            expect(wrapper.contains("del")).toBeFalsy();
        });
    });
});
