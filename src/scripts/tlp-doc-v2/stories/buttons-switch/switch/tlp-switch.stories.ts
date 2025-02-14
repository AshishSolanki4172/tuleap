/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Meta, StoryObj } from "@storybook/web-components";
import type { TemplateResult } from "lit";
import { html } from "lit";
import "@tuleap/tlp-button";

type SwitchProps = {
    in_form: boolean;
    form_label: string;
    size: string;
    disabled: boolean;
    checked: boolean;
};

const SWITCH_SIZES = ["default", "large", "mini"];

function getClasses(args: SwitchProps): string {
    const classes = [`tlp-switch`];
    if (args.size !== "default") {
        classes.push(`tlp-switch-${args.size}`);
    }
    return classes.join(" ");
}

function getTemplate(args: SwitchProps): TemplateResult {
    if (args.in_form) {
        //prettier-ignore
        return html`
<div class="tlp-form-element">
    <label class="tlp-label" for="toggle">${args.form_label}</label>
    <div class=${getClasses(args)}>
        <input type="checkbox" id="toggle" class="tlp-switch-checkbox" ?disabled=${args.disabled} ?checked=${args.checked}>
        <label for="toggle" class="tlp-switch-button"></label>
    </div>
</div>`;
    }
    //prettier-ignore
    return html`
<div class=${getClasses(args)}>
    <input type="checkbox" id="toggle" class="tlp-switch-checkbox" ?disabled=${args.disabled} ?checked=${args.checked}>
    <label for="toggle" class="tlp-switch-button"></label>
</div>`;
}

const meta: Meta<SwitchProps> = {
    title: "TLP/Buttons & Switch/Switch",
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        in_form: false,
        form_label: "Activate advanced mode",
        size: "default",
        disabled: false,
        checked: true,
    },
    argTypes: {
        in_form: {
            name: "In form",
            description: "Put the switch in a form.",
            table: {
                type: { summary: null },
            },
        },
        form_label: {
            name: "Form label",
            if: { arg: "in_form" },
        },
        size: {
            name: "Size",
            description: "Switch size, applies the class",
            control: "select",
            options: SWITCH_SIZES,
            table: {
                type: { summary: ".tlp-switch-[size]" },
            },
        },
        disabled: {
            name: "Disabled",
            description: "Add disabled attribute.",
            table: {
                type: { summary: null },
            },
        },
        checked: {
            name: "Checked",
            description: "Add checked attribute.",
            table: {
                type: { summary: null },
            },
        },
    },
};

export default meta;
type Story = StoryObj<SwitchProps>;

export const Switch: Story = {};
