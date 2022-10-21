/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { get } from "@tuleap/tlp-fetch";
import type { FileHistory, LinkVersion } from "../type";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getAllJSON, del } from "@tuleap/fetch-result";

export { getFileVersionHistory, getAllFileVersionHistory, getAllLinkVersionHistory, deleteVersion };

async function getFileVersionHistory(id: number): Promise<ReadonlyArray<FileHistory>> {
    const escaped_file_id = encodeURIComponent(id);
    const versions = await get(`/api/docman_files/${escaped_file_id}/versions`, {
        params: {
            limit: 5,
            offset: 0,
        },
    });

    return versions.json();
}

function getAllFileVersionHistory(id: number): ResultAsync<readonly FileHistory[], Fault> {
    return getAllJSON<readonly FileHistory[], FileHistory>(`/api/docman_files/${id}/versions`);
}

function getAllLinkVersionHistory(id: number): ResultAsync<readonly LinkVersion[], Fault> {
    return getAllJSON<readonly LinkVersion[], LinkVersion>(`/api/docman_links/${id}/version`, {
        params: { limit: 50 },
    });
}

function deleteVersion(id: number): ResultAsync<Response, Fault> {
    return del(`/api/docman_file_versions/${id}`);
}
