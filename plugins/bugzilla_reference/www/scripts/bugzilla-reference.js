/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
document.addEventListener('DOMContentLoaded', function () {
    var add_reference = document.querySelector('#bugzilla-add-reference'),
        add_reference_bugzilla = document.querySelector('#bugzilla-add-reference-modal');
        modal_add_reference = tlp.modal(add_reference_bugzilla, {keyboard: true});

    add_reference.addEventListener('click', function () {
        modal_add_reference.toggle();
    });

    var bugzilla_modals_edit_buttons = document.querySelectorAll('.bugzilla-edit-modal');
    [].forEach.call(bugzilla_modals_edit_buttons, function(bugzilla_modals_edit_button) {
        var dom_bugzilla_modal_edit = document.getElementById(bugzilla_modals_edit_button.getAttribute('data-edit-modal-id'));
        var tlp_bugzilla_modal_edit = tlp.modal(dom_bugzilla_modal_edit);

        bugzilla_modals_edit_button.addEventListener('click', function () {
            tlp_bugzilla_modal_edit.toggle();
        });
    });
});
