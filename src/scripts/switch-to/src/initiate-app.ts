/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import Vue from "vue";
import { PiniaVuePlugin, createPinia } from "pinia";
import { initVueGettext, getPOFileFromLocale } from "@tuleap/vue2-gettext-init";
import type { VueClass } from "vue-class-component/lib/declarations";
import type { State } from "./stores/type";
import { useSwitchToStore } from "./stores";

export async function init(vue_mount_point: HTMLElement, component: VueClass<Vue>): Promise<void> {
    await initVueGettext(
        Vue,
        (locale: string) =>
            import(/* webpackChunkName: "switch-to-po-" */ "../po/" + getPOFileFromLocale(locale))
    );

    Vue.use(PiniaVuePlugin);

    const pinia = createPinia();
    const root_state: State = {
        projects:
            typeof vue_mount_point.dataset.projects !== "undefined"
                ? JSON.parse(vue_mount_point.dataset.projects)
                : [],
        is_trove_cat_enabled: Boolean(vue_mount_point.dataset.isTroveCatEnabled),
        are_restricted_users_allowed: Boolean(vue_mount_point.dataset.areRestrictedUsersAllowed),
        is_search_available: Boolean(vue_mount_point.dataset.isSearchAvailable),
        filter_value: "",
        search_form:
            typeof vue_mount_point.dataset.searchForm !== "undefined"
                ? JSON.parse(vue_mount_point.dataset.searchForm)
                : { type_of_search: "soft", hidden_fields: [] },
        user_id: parseInt(document.body.dataset.userId || "0", 10),
        is_loading_history: true,
        is_history_loaded: false,
        is_history_in_error: false,
        history: { entries: [] },
        programmatically_focused_element: null,
    };

    const AppComponent = Vue.extend(component);
    new AppComponent({
        pinia,
    }).$mount(vue_mount_point);

    const store = useSwitchToStore();
    store.$patch(root_state);
}
