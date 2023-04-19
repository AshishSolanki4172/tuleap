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

export function isEscapeKey(event: KeyboardEvent): boolean {
    return event.key === "Escape" || event.key === "Esc";
}

export function isEnterKey(event: KeyboardEvent): boolean {
    return event.key === "Enter";
}

export function isArrowDown(event: KeyboardEvent): boolean {
    return event.key === "ArrowDown";
}

export function isArrowUp(event: KeyboardEvent): boolean {
    return event.key === "ArrowUp";
}

export function isTabKey(event: KeyboardEvent): boolean {
    return event.key === "Tab";
}

export function isBackspaceKey(event: KeyboardEvent): boolean {
    return event.key === "Backspace";
}
