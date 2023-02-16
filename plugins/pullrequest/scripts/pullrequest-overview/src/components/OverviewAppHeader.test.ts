/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import OverviewAppHeader from "./OverviewAppHeader.vue";
import type { PullRequestInfo } from "../api/types";

describe("OverviewAppHeader", () => {
    it("should render a skeleton in place of the title when there is no pull request info yet", async () => {
        const wrapper = shallowMount(OverviewAppHeader, {
            props: {
                pull_request: null,
            },
        });

        expect(wrapper.find("[data-test=pullrequest-title-skeleton]").exists()).toBe(true);

        wrapper.setProps({
            pull_request: {
                title: "My pull request title",
            } as PullRequestInfo,
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=pullrequest-title-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pullrequest-title]").text()).toBe("My pull request title");
    });
});
