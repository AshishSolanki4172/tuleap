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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import ConfirmReplaceTokenModal from "./ConfirmReplaceTokenModal.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import type { Store } from "vuex-mock-store";

describe("ConfirmReplaceTokenModal", () => {
    let store_options = {},
        propsData = {},
        localVue,
        store: Store;

    beforeEach(() => {
        store_options = {
            state: {},
            getters: {},
        };
    });

    function instantiateComponent(): Wrapper<ConfirmReplaceTokenModal> {
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        store = createStoreMock(store_options);

        return shallowMount(ConfirmReplaceTokenModal, {
            propsData,
            mocks: { $store: store },
            localVue,
        });
    }

    it("When the user confirms new token, Then api is called and event is emitted", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeFalsy();

        wrapper.find("[data-test=button-confirm-edit-token-gitlab]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-confirm-edit-token-gitlab]").attributes().disabled
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeTruthy();

        expect(store.dispatch).toHaveBeenCalledWith("updateBotApiTokenGitlab", {
            gitlab_bot_api_token: "AZRERT123",
            gitlab_repository_id: 12,
            gitlab_repository_url: "https://example.com/my/repo",
        });

        const on_success_edit_token = wrapper.emitted()["on-success-edit-token"];
        if (!on_success_edit_token) {
            throw new Error("Should have emitted on-success-edit-token");
        }

        expect(on_success_edit_token[0]).toEqual([]);
    });

    it("When there is an error message, Then it's displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();

        wrapper.setData({
            message_error_rest: "Error message",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-patch-edit-token]").text()).toEqual(
            "Error message"
        );
        expect(
            wrapper.find("[data-test=button-confirm-edit-token-gitlab]").attributes().disabled
        ).toBeTruthy();
    });

    it("When user submit but there are errors, Then nothing happens", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();

        wrapper.setData({
            message_error_rest: "Error message",
        });

        await wrapper.vm.$nextTick();

        wrapper.find("[data-test=button-confirm-edit-token-gitlab]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(store.dispatch).not.toHaveBeenCalled();
    });

    it("When api throws an error, Then error message is displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(
            Promise.reject({
                response: {
                    status: 404,
                    json: (): Promise<{ error: { code: number; message: string } }> =>
                        Promise.resolve({ error: { code: 404, message: "Error on server" } }),
                },
            })
        );

        wrapper.find("[data-test=button-confirm-edit-token-gitlab]").trigger("click");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.message_error_rest).toEqual("404 Error on server");
    });

    it("When user back to form, Then event is emitted", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_new_token: "AZRERT123",
        };

        const wrapper = instantiateComponent();

        wrapper.find("[data-test=button-gitlab-edit-token-back]").trigger("click");
        await wrapper.vm.$nextTick();

        const on_back_button = wrapper.emitted()["on-back-button"];
        if (!on_back_button) {
            throw new Error("Should have emitted on-back-button");
        }

        expect(on_back_button[0]).toStrictEqual([]);
    });
});
