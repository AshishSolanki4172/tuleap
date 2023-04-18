/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { ConditionPredicate, ReloadCallback } from "./commands-type-definitions";
import type {
    StructureFields,
    ListNewChangesetValue,
    StaticBoundListField,
} from "@tuleap/plugin-tracker-rest-api-types";

export const WEB_UI_SESSION = "WebUI";

Cypress.Commands.add("clearSessionCookie", () => {
    cy.clearCookie("__Host-TULEAP_session_hash");
});

Cypress.Commands.add("preserveSessionCookies", () => {
    Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
});

Cypress.Commands.add("projectAdministratorSession", () => {
    sessionThroughWebUI("ProjectAdministrator", "Correct Horse Battery Staple");
});

Cypress.Commands.add("projectMemberSession", () => {
    sessionThroughWebUI("ProjectMember", "Correct Horse Battery Staple");
});

Cypress.Commands.add("siteAdministratorSession", () => {
    sessionThroughWebUI("admin", "welcome0");
});

Cypress.Commands.add("regularUserSession", () => {
    sessionThroughWebUI("RegularUser", "Correct Horse Battery Staple");
});

Cypress.Commands.add("anonymousSession", () => {
    cy.session([WEB_UI_SESSION, "/anonymous"], () => {
        cy.visit("/");
        // Do not log in
    });
});

Cypress.Commands.add("projectAdministratorLogin", () => {
    loginThroughWebUI("ProjectAdministrator", "Correct Horse Battery Staple");
});

Cypress.Commands.add("projectMemberLogin", () => {
    loginThroughWebUI("ProjectMember", "Correct Horse Battery Staple");
});

Cypress.Commands.add("restrictedMemberSession", () => {
    sessionThroughWebUI("RestrictedMember", "Correct Horse Battery Staple");
});

Cypress.Commands.add("restrictedRegularUserSession", () => {
    sessionThroughWebUI("RestrictedRegularUser", "Correct Horse Battery Staple");
});

function loginThroughWebUI(username: string, password: string): void {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type(username);
    cy.get("[data-test=form_pw]").type(`${password}{enter}`);
}

function sessionThroughWebUI(username: string, password: string): void {
    cy.session([WEB_UI_SESSION, username], () => {
        loginThroughWebUI(username, password);
    });
}

Cypress.Commands.add("userLogout", () => {
    cy.get("[data-test=user_logout]").click({ force: true });
});

interface CacheServiceUrls {
    [key: string]: string;
}

interface CacheProjectUrls {
    [key: string]: CacheServiceUrls;
}
const cache_service_urls: CacheProjectUrls = {};
Cypress.Commands.add(
    "visitProjectService",
    (project_unixname: string, service_label: string): void => {
        if (
            Object.prototype.hasOwnProperty.call(cache_service_urls, project_unixname) &&
            Object.prototype.hasOwnProperty.call(
                cache_service_urls[project_unixname],
                service_label
            )
        ) {
            cy.visit(cache_service_urls[project_unixname][service_label]);
            return;
        }

        cy.visit("/projects/" + project_unixname);
        visitServiceInCurrentProject(service_label, (href) => {
            cache_service_urls[project_unixname] = cache_service_urls[project_unixname] || {};
            cache_service_urls[project_unixname][service_label] = href;
        });
    }
);

Cypress.Commands.add("visitProjectAdministration", (project_unixname: string) => {
    cy.visit("/projects/" + project_unixname);
    cy.get('[data-test="project-administration-link"]', { includeShadowDom: true }).click();
});

Cypress.Commands.add("visitProjectAdministrationInCurrentProject", () => {
    cy.get('[data-test="project-administration-link"]', { includeShadowDom: true }).click();
});

Cypress.Commands.add("visitServiceInCurrentProject", (service_label: string) => {
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    visitServiceInCurrentProject(service_label, () => {});
});

function visitServiceInCurrentProject(
    service_label: string,
    before_visit_callback: (href: string) => void
): void {
    cy.get("[data-test=project-sidebar-tool]", { includeShadowDom: true })
        .contains("[data-test=project-sidebar-tool]", service_label, { includeShadowDom: true })
        .should("have.attr", "href")
        .then((href) => {
            before_visit_callback(String(href));
            cy.visit(String(href));
        });
}

Cypress.Commands.add("updatePlatformVisibilityAndAllowRestricted", (): void => {
    cy.visit("/admin/");
    cy.get("[data-test=global_access_right]").click({ force: true });

    cy.get("[data-test=access_mode-restricted]").check();

    cy.get("[data-test=update_forge_access_button]").click({ force: true });

    cy.get("[data-test=global-admin-search-user]").type("RestrictedMember{enter}");
    cy.get("[data-test=user-status]").select("Restricted");
    cy.get("[data-test=save-user]").click();

    cy.get("[data-test=global-admin-search-user]").type("RestrictedRegularUser{enter}");
    cy.get("[data-test=user-status]").select("Restricted");
    cy.get("[data-test=save-user]").click();
});

Cypress.Commands.add("updatePlatformVisibilityForAnonymous", (): void => {
    cy.visit("/admin/");
    cy.get("[data-test=global_access_right]").click({ force: true });

    cy.get("[data-test=access_mode-anonymous]").check();

    cy.get("[data-test=update_forge_access_button]").click({ force: true });
});

Cypress.Commands.add("getProjectId", (project_shortname: string): Cypress.Chainable<number> => {
    return cy
        .getFromTuleapAPI(
            `/api/projects?limit=1&query=${encodeURIComponent(
                JSON.stringify({ shortname: project_shortname })
            )}`
        )
        .then((response) => response.body[0].id);
});

Cypress.Commands.add("switchProjectVisibility", (visibility: string): void => {
    cy.get("[data-test=admin-nav-details]").click();
    cy.get("[data-test=project_visibility]").select(visibility);
    cy.get("[data-test=project-details-short-description-input]").type("My short description");
    cy.get("[data-test=project-details-submit-button]").click();
    cy.get("[data-test=term_of_service]").click({ force: true });

    cy.get("[data-test=project-details-submit-button]").click();
});

Cypress.Commands.add(
    "createNewPublicProject",
    (project_name: string, xml_template: string): Cypress.Chainable<number> => {
        const payload = {
            shortname: project_name,
            description: "",
            label: project_name,
            is_public: true,
            categories: [],
            fields: [],
            xml_template_name: xml_template,
            allow_restricted: false,
        };

        return cy.postFromTuleapApi("https://tuleap/api/projects/", payload).then((response) => {
            return Number.parseInt(response.body.id, 10);
        });
    }
);

Cypress.Commands.add("createNewPrivateProject", (project_name: string): void => {
    const payload = {
        shortname: project_name,
        description: "",
        label: project_name,
        is_public: false,
        categories: [],
        fields: [],
        xml_template_name: "issues",
        allow_restricted: true,
    };

    cy.postFromTuleapApi("https://tuleap/api/projects/", payload);
});

Cypress.Commands.add("addProjectMember", (user_name: string): void => {
    cy.visitProjectAdministrationInCurrentProject();
    cy.get("[data-test=project-admin-members-add-user-select] + .select2-container").click();

    cy.get(".select2-search__field").type(`${user_name}{enter}`);

    cy.get(".select2-result-user").click();
    cy.get('[data-test="project-admin-submit-add-member"]').click();
});

interface Tracker {
    id: number;
    item_name: string;
}

Cypress.Commands.add(
    "getTrackerIdFromREST",
    (project_id: number, tracker_name: string): Cypress.Chainable<number> => {
        return cy.getFromTuleapAPI(`/api/projects/${project_id}/trackers`).then((response) => {
            return response.body.find((tracker: Tracker) => tracker.item_name === tracker_name).id;
        });
    }
);

export interface ArtifactCreationPayload {
    tracker_id: number;
    artifact_title: string;
    artifact_status?: string;
    title_field_name: string;
}

const statusFieldGuard = (field: StructureFields): field is StaticBoundListField =>
    field.type === "sb" && field.bindings.type === "static";

function getStatusPayload(
    status_label: string | undefined,
    fields: readonly StructureFields[]
): ListNewChangesetValue[] {
    if (status_label === undefined) {
        return [];
    }
    const status = fields.find((field) => field.name === "status");
    if (!status || !statusFieldGuard(status)) {
        throw Error("No status field in tracker structure");
    }
    const status_id = status.field_id;
    const status_bind_value = status.values.find((value) => value.label === status_label);
    if (status_bind_value === undefined) {
        throw Error(`Could not find status value with given label: ${status_label}`);
    }
    return [
        {
            bind_value_ids: [status_bind_value.id],
            field_id: status_id,
        },
    ];
}

Cypress.Commands.add(
    "createArtifact",
    (payload: ArtifactCreationPayload): Cypress.Chainable<number> =>
        cy.getFromTuleapAPI(`/api/trackers/${payload.tracker_id}`).then((response) => {
            const result = response.body;

            const title_id = result.fields.find(
                (field: StructureFields) => field.name === payload.title_field_name
            ).field_id;
            const artifact_payload = {
                tracker: { id: payload.tracker_id },
                values: [
                    {
                        field_id: title_id,
                        value: payload.artifact_title,
                    },
                    ...getStatusPayload(payload.artifact_status, result.fields),
                ],
            };

            return cy
                .postFromTuleapApi("/api/artifacts/", artifact_payload)
                .then((response) => response.body.id);
        })
);

Cypress.Commands.add("createFRSPackage", (project_id: number, package_name: string): void => {
    const payload = {
        project_id: project_id,
        label: package_name,
    };

    cy.postFromTuleapApi("https://tuleap/api/frs_packages/", payload);
});

const MAX_ATTEMPTS = 10;

Cypress.Commands.add(
    "reloadUntilCondition",
    (
        reloadCallback: ReloadCallback,
        conditionCallback: ConditionPredicate,
        max_attempts_reached_message: string,
        number_of_attempts = 0
    ): PromiseLike<void> => {
        if (number_of_attempts > MAX_ATTEMPTS) {
            throw new Error(max_attempts_reached_message);
        }
        return conditionCallback(number_of_attempts, MAX_ATTEMPTS).then(
            (is_condition_fulfilled) => {
                if (is_condition_fulfilled) {
                    return Promise.resolve();
                }

                cy.wait(100);
                reloadCallback();
                return cy.reloadUntilCondition(
                    reloadCallback,
                    conditionCallback,
                    max_attempts_reached_message,
                    number_of_attempts + 1
                );
            }
        );
    }
);

Cypress.Commands.add(
    "getContains",
    (selector: string, label: string): Cypress.Chainable<JQuery<HTMLElement>> => {
        return cy.get(selector).contains(label).parents(selector);
    }
);

export {};
