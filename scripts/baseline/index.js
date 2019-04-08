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
import french_translations from "./po/fr.po";
import GetTextPlugin from "vue-gettext";
import router from "./router";
import App from "./components/App.vue";
import store from "./store";
import DateUtils from "./support/date-utils";

document.addEventListener("DOMContentLoaded", () => {
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages
        },
        silent: true
    });

    let user_locale = document.body.dataset.userLocale;
    Vue.config.language = user_locale;

    DateUtils.setOptions({
        user_locale,
        user_timezone: document.body.dataset.userTimezone,
        format: document.body.dataset.dateTimeFormat
    });

    const vue_mount_point = document.getElementById("baseline-container");

    if (!vue_mount_point) {
        return;
    }

    const project_id = Number(vue_mount_point.dataset.projectId);

    const AppComponent = Vue.extend(App);
    new AppComponent({
        store,
        propsData: { project_id },
        router
    }).$mount(vue_mount_point);
});
