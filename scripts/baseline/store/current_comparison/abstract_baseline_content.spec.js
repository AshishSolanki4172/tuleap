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

import store from "./abstract_baseline_content";
import { create, createList } from "../../support/factories";
import {
    restore,
    rewire$getBaselineArtifacts,
    rewire$getBaselineArtifactsByIds
} from "../../api/rest-querier";

describe("Compared baseline store:", () => {
    let state;
    beforeEach(() => (state = { ...store.state }));

    describe("actions", () => {
        let context;
        let getBaselineArtifacts;
        let getBaselineArtifactsByIds;

        beforeEach(() => {
            context = {
                state: { ...state, baseline_id: 1 },
                commit: jasmine.createSpy("commit"),
                dispatch: jasmine.createSpy("dispatch")
            };
            context.dispatch.and.returnValue(Promise.resolve());

            getBaselineArtifacts = jasmine.createSpy("getBaselineArtifacts");
            rewire$getBaselineArtifacts(getBaselineArtifacts);

            getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
            rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);
        });

        afterEach(restore);

        describe("#loadAllArtifacts", () => {
            const artifacts = createList("baseline_artifact", 2);
            beforeEach(() => {
                getBaselineArtifacts.withArgs(1).and.returnValue(Promise.resolve(artifacts));
                return store.actions.loadAllArtifacts(context);
            });

            it("commits 'updateFirstLevelArtifacts' with baseline artifacts", () => {
                expect(context.commit).toHaveBeenCalledWith("updateFirstLevelArtifacts", artifacts);
            });
            it("dispatches 'addArtifacts' with baseline artifacts", () => {
                expect(context.dispatch).toHaveBeenCalledWith("addArtifacts", artifacts);
            });
        });

        describe("#addArtifacts", () => {
            describe("when some linked artifacts", () => {
                const artifacts = [
                    create("baseline_artifact", { linked_artifact_ids: [1] }),
                    create("baseline_artifact", { linked_artifact_ids: [2, 3] })
                ];

                const linked_artifacts = createList("baseline_artifact", 3);

                beforeEach(() => {
                    getBaselineArtifactsByIds
                        .withArgs(1, [1, 2, 3])
                        .and.returnValue(Promise.resolve(linked_artifacts));

                    context.state.baseline_id = 1;
                    return store.actions.addArtifacts(context, artifacts);
                });

                it("commit 'incrementLoadedDepthsCount'", () => {
                    expect(context.commit).toHaveBeenCalledWith("incrementLoadedDepthsCount");
                });

                it("commit 'addArtifacts' with artifacts", () => {
                    expect(context.commit).toHaveBeenCalledWith("addArtifacts", artifacts);
                });

                it("dispatch 'addArtifacts' with linked artifacts", () => {
                    expect(context.dispatch).toHaveBeenCalledWith("addArtifacts", linked_artifacts);
                });
            });

            describe("when no linked artifacts", () => {
                beforeEach(() => {
                    const artifacts = [
                        create("baseline_artifact", { linked_artifact_ids: [] }),
                        create("baseline_artifact", { linked_artifact_ids: [] })
                    ];
                    return store.actions.addArtifacts(context, artifacts);
                });
                it("does not dispatch 'addArtifacts'", () => {
                    expect(context.dispatch).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe("mutations", () => {
        describe("after comparison is reset", () => {
            beforeEach(() => store.mutations.reset(state, { baseline_id: 1 }));

            describe("#addArtifacts", () => {
                const artifact1 = create("baseline_artifact", { id: 1 });
                const artifact2 = create("baseline_artifact", { id: 2 });

                beforeEach(() => store.mutations.addArtifacts(state, [artifact1, artifact2]));

                it("adds given artifacts", () => {
                    expect(state.artifacts_by_id[1]).toEqual(artifact1);
                    expect(state.artifacts_by_id[2]).toEqual(artifact2);
                });
            });
        });
    });

    describe("getters", () => {
        describe("#findArtifactsByIds", () => {
            const artifact1 = create("baseline_artifact");
            const artifact2 = create("baseline_artifact");
            beforeEach(() =>
                (state.artifacts_by_id = {
                    1: artifact1,
                    2: artifact2
                }));
            it("returns all base artifacts with given ids", () => {
                expect(store.getters.findArtifactsByIds(state)([1, 2])).toEqual([
                    artifact1,
                    artifact2
                ]);
            });
        });
        describe("#is_depth_limit_reached", () => {
            describe("when no artifacts on depth limit", () => {
                beforeEach(() => (state.artifacts_where_depth_limit_reached = null));
                it("returns false", () => {
                    expect(store.getters.is_depth_limit_reached(state)).toBeFalsy();
                });
            });
            describe("when some artifacts on depth limit", () => {
                beforeEach(() =>
                    (state.artifacts_where_depth_limit_reached = createList(
                        "baseline_artifact",
                        2
                    )));
                it("returns true", () => {
                    expect(store.getters.is_depth_limit_reached(state)).toBeTruthy();
                });
            });
        });
        describe("#isLimitReachedOnArtifact", () => {
            const artifact = create("baseline_artifact");
            const getters = {};

            describe("when depth limit not reached", () => {
                beforeEach(() => (getters.is_depth_limit_reached = false));
                it("returns false", () => {
                    expect(
                        store.getters.isLimitReachedOnArtifact(state, getters)(artifact)
                    ).toBeFalsy();
                });
            });

            describe("when depth limit reached", () => {
                beforeEach(() => (getters.is_depth_limit_reached = true));

                describe("on given artifact", () => {
                    beforeEach(() => (state.artifacts_where_depth_limit_reached = [artifact]));
                    it("returns true", () => {
                        expect(
                            store.getters.isLimitReachedOnArtifact(state, getters)(artifact)
                        ).toBeTruthy();
                    });
                });
                describe("not reached on given artifact", () => {
                    beforeEach(() =>
                        (state.artifacts_where_depth_limit_reached = createList(
                            "baseline_artifact",
                            2
                        )));
                    it("returns false", () => {
                        expect(
                            store.getters.isLimitReachedOnArtifact(state, getters)(artifact)
                        ).toBeFalsy();
                    });
                });
            });
        });
    });
});
