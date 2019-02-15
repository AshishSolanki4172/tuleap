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

import Vue from "vue";
import App from "./components/App.vue";
import french_translations from "./po/fr.po";
import GetTextPlugin from "vue-gettext";

document.addEventListener("DOMContentLoaded", () => {
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages
        },
        silent: true
    });

    Vue.config.language = document.body.dataset.userLocale;

    const vue_mount_point = document.getElementById("baseline-container");

    if (!vue_mount_point) {
        return;
    }

    const AppComponent = Vue.extend(App);
    const url_params = new URLSearchParams(window.location.search);
    new AppComponent({
        propsData: {
            artifact_id: Number(url_params.get("artifact_id")),
            date: url_params.get("date")
        }
    }).$mount(vue_mount_point);
});
