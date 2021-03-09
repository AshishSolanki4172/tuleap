/**
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import {
    getAsyncGitlabRepositoryList,
    deleteIntegrationGitlab,
    getGitlabRepositoryList,
    patchGitlabRepository,
    postGitlabRepository,
} from "./gitlab-api-querier";
import * as tlp from "tlp";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper.js";
import type { RecursiveGetInit } from "@tuleap/tlp-fetch";
import type { Repository } from "../type";
import type { GitLabRepositoryUpdate } from "./gitlab-api-querier";

jest.mock("tlp");

describe("Gitlab Api Querier", () => {
    it("When api is called, Then the request with correct headers is sent", async () => {
        const credentials = {
            server_url: "https://example.com",
            token: "azerty1234",
        };

        const headers = new Headers();
        headers.append("Authorization", "Bearer " + credentials.token);

        await getAsyncGitlabRepositoryList(credentials);

        expect(tlp.get).toHaveBeenCalledWith("https://example.com", {
            cache: "default",
            headers,
            mode: "cors",
        });
    });

    describe("getGitlabRepositoryList", () => {
        it("Given a project id and a callback, Then it will recursively get all Gitlab repositories and call the callback for each batch", () => {
            return new Promise<void>((done) => {
                const repositories = [{ id: 37 } as Repository, { id: 91 } as Repository];

                jest.spyOn(tlp, "recursiveGet").mockImplementation(
                    <T>(
                        url: string,
                        init?: RecursiveGetInit<Array<Repository>, T>
                    ): Promise<T[]> => {
                        if (!init || !init.getCollectionCallback) {
                            throw new Error();
                        }

                        return Promise.resolve(init.getCollectionCallback(repositories));
                    }
                );

                function displayCallback(result: Array<Repository>): void {
                    expect(result).toEqual(repositories);
                    done();
                }

                const project_id = 27;

                getGitlabRepositoryList(project_id, "push_date", displayCallback);
            });
        });
    });

    describe("deleteIntegrationGitlab", () => {
        it("Given project id and repository id, Then api is queried to delete", async () => {
            const project_id = 101;
            const repository_id = 1;

            const tlpDelete = jest.spyOn(tlp, "del");
            mockFetchSuccess(tlpDelete);

            await deleteIntegrationGitlab({ project_id, repository_id });

            expect(tlpDelete).toHaveBeenCalledWith(
                "/api/v1/gitlab_repositories/" + repository_id + "?project_id=" + project_id
            );
        });
    });

    describe("postGitlabRepository", () => {
        it("Given project id and repository, token and server url, Then api is queried to create new integration", async () => {
            const project_id = 101;
            const gitlab_repository_id = 10;
            const gitlab_bot_api_token = "AzRT785";
            const gitlab_server_url = "https://example.com";

            const headers = {
                "content-type": "application/json",
            };

            const body = JSON.stringify({
                project_id,
                gitlab_repository_id,
                gitlab_server_url,
                gitlab_bot_api_token,
            });

            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost);

            await postGitlabRepository({
                project_id,
                gitlab_repository_id,
                gitlab_server_url,
                gitlab_bot_api_token,
            });

            expect(tlpPost).toHaveBeenCalledWith("/api/gitlab_repositories", {
                headers,
                body,
            });
        });
    });

    describe("patchGitlabRepository", () => {
        it("Given body, Then api is queried to patch gitlab repository", async () => {
            const headers = {
                "content-type": "application/json",
            };

            const body = {
                update_bot_api_token: {
                    gitlab_bot_api_token: "AZERTY12345",
                    gitlab_repository_id: 20,
                    gitlab_repository_url: "https://example.com",
                },
            } as GitLabRepositoryUpdate;

            const body_stringify = JSON.stringify(body);

            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);

            await patchGitlabRepository(body);

            expect(tlpPatch).toHaveBeenCalledWith("/api/gitlab_repositories", {
                headers,
                body: body_stringify,
            });
        });
    });
});
