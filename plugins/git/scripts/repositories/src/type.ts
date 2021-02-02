/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { Modal } from "tlp";

export interface State {
    repositories_for_owner: string;
    filter: string;
    selected_owner_id: string | number;
    error_message_type: number;
    success_message: string;
    is_loading_initial: boolean;
    is_loading_next: boolean;
    add_repository_modal: null | Modal;
    display_mode: string;
    is_first_load_done: boolean;
    services_name_used: string[];
    add_gitlab_repository_modal: null | Modal;
    unlink_gitlab_repository_modal: null | Modal;
    unlink_gitlab_repository: null | Repository;
}

export interface Repository {
    id: string | number;
    integration_id: string | number;
    description: string;
    label: string;
    last_update_date: string;
    last_push_date: string;
    additional_information: [];
    normalized_path?: string;
    path_without_project: string;
    uri: string;
    name: string;
    path: string;
    permissions: {
        read: PermissionsRepository[];
        write: PermissionsRepository[];
        rewind: PermissionsRepository[];
    };
    server: null | string;
    html_url: string;
    gitlab_data?: null | GitLabData;
}

export interface GitlabDataWithPath {
    normalized_path: string;
    gitlab_data: GitLabData;
}

export interface PermissionsRepository {
    id: string;
    uri: string;
    label: string;
    users_uri: string;
    short_name: string;
    key: string;
}

export interface GitLabData {
    gitlab_repository_url: string;
    gitlab_repository_id: number;
}

export interface GitLabDataWithToken extends GitLabData {
    gitlab_bot_api_token: string;
}

export interface GitLabDataToPostAPI {
    gitlab_bot_api_token: string;
    gitlab_server_url: string;
    gitlab_repository_id: number;
    project_id: number;
}

export interface Folder {
    is_folder: boolean;
    label: string;
    children: Map<string, Folder | Repository> | Array<Folder | Repository>;
}

export interface GitLabCredentials {
    token: string;
    server_url: string;
}

export interface GitLabCredentialsWithProjects extends GitLabCredentials {
    projects: GitlabProject[];
}

export interface GitLabRepository {
    description: string;
    gitlab_repository_url: string;
    gitlab_repository_id: number;
    id: number;
    last_push_date: string;
    name: string;
}

export interface FormattedGitLabRepository {
    id: string | number;
    integration_id: string | number;
    description: string;
    label: string;
    last_update_date: string;
    additional_information: [];
    normalized_path?: string;
    path_without_project: string;
    gitlab_data?: null | GitLabData;
}

export interface RepositoryOwner {
    display_name: string;
}

export interface ExternalPlugins {
    plugin_name: string;
    data: Array<unknown>;
}

export interface GitlabProject {
    id: number;
    path_with_namespace: string;
    name_with_namespace: string;
    avatar_url: string;
    web_url: string;
}
