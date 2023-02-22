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

import { describe, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import PullRequestErrorModal from "./PullRequestErrorModal.vue";
import { getGlobalTestOptions } from "../../tests-helpers/global-options-for-tests";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { Fault } from "@tuleap/fault";

vi.mock("@tuleap/tlp-modal", () => ({
    createModal: vi.fn(),
}));

const getWrapper = (fault: Fault | null): VueWrapper => {
    return shallowMount(PullRequestErrorModal, {
        global: {
            ...getGlobalTestOptions(),
        },
        props: {
            fault: fault,
        },
    });
};

describe("PullRequestErrorModal", () => {
    it("When a fault has been detected, it shows the modal", async () => {
        const modal_instance = {
            show: vi.fn(),
        } as unknown as Modal;

        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);

        const wrapper = getWrapper(null);

        expect(tlp_modal.createModal).toHaveBeenCalledOnce();
        expect(modal_instance.show).not.toHaveBeenCalled();

        wrapper.setProps({
            fault: Fault.fromMessage("Something wrong has occurred."),
        });

        await wrapper.vm.$nextTick();

        expect(modal_instance.show).toHaveBeenCalledOnce();
    });

    it("Shows the error details when the user clicks on [Show details]", async () => {
        const fault = Fault.fromMessage("Forbidden: Nope");
        const wrapper = getWrapper(fault);

        expect(wrapper.find("[data-test=pullrequest-error-modal-details]").exists()).toBe(false);
        wrapper
            .find<HTMLButtonElement>("[data-test=pullrequest-error-modal-show-details]")
            .trigger("click");

        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=pullrequest-error-modal-details]").exists()).toBe(true);
        expect(
            wrapper.find("[data-test=pullrequest-error-modal-details-message]").text()
        ).toStrictEqual(String(fault));
    });
});
