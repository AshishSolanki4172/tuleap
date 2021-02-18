/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

const path = require("path");
const webpack_configurator = require("../../../../../tools/utils/scripts/webpack-configurator.js");

const context = __dirname;

const webpack_config = {
    entry: {
        "rich-text-editor": "./src/index.ts",
    },
    context,
    output: {
        path: path.join(context, "./dist/"),
        library: "PluginTrackerRichTextEditor",
        libraryTarget: "umd",
    },
    externals: {
        ckeditor4: "ckeditor4",
        jquery: "jquery",
    },
    resolve: {
        extensions: [".ts", ".js"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptLibraryRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [webpack_configurator.getCleanWebpackPlugin()],
};

module.exports = [webpack_config];
