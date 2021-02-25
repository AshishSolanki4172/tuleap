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

import hotkeys from "hotkeys-js";

import { handleCreateShortcut } from "./handle-global-shortcuts/handle-create-shortcut";
import { handleSearchShortcut } from "./handle-global-shortcuts/handle-search-shortcut";
import { handleDashboardShortcut } from "./handle-global-shortcuts/handle-dashboard-shortcut";
import { handleHelpShortcut } from "./handle-global-shortcuts/handle-help-shortcut";

import { HOTKEYS_SCOPE_NO_MODAL } from "@tuleap/keyboard-shortcuts";

hotkeys("c", HOTKEYS_SCOPE_NO_MODAL, () => {
    handleCreateShortcut();
});

hotkeys("/,s", HOTKEYS_SCOPE_NO_MODAL, (event: KeyboardEvent) => {
    handleSearchShortcut(event);
});

hotkeys("d", HOTKEYS_SCOPE_NO_MODAL, () => {
    handleDashboardShortcut();
});

hotkeys("*", HOTKEYS_SCOPE_NO_MODAL, (event) => {
    // Should be hotkeys("?", …),
    // however for unknown reason it does not work (maybe due to shift key?),
    // therefore we're using wildcard as a workaround
    if (event.key !== "?") {
        return;
    }
    handleHelpShortcut();
});
