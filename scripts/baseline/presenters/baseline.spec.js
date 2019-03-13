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

import { restore, rewire$getUser } from "../api/rest-querier";
import { presentBaselines } from "./baseline";

describe("baseline presenter:", () => {
    afterEach(restore);

    describe("presentBaselines()", () => {
        let getUser;
        let getUserResolve;
        let getUserReject;
        let presentation;

        beforeEach(() => {
            getUser = jasmine.createSpy("getUser");
            getUser.and.returnValue(
                new Promise((resolve, reject) => {
                    getUserResolve = resolve;
                    getUserReject = reject;
                })
            );
            rewire$getUser(getUser);
        });

        describe("when single baseline", () => {
            beforeEach(() => {
                presentation = presentBaselines([{ author_id: 1 }]);
            });

            it("calls getUser when author id", () => expect(getUser).toHaveBeenCalledWith(1));

            describe("when getUser() is successful", () => {
                const user = { id: 1, username: "John Doe" };
                let presented_baselines;

                beforeEach(async () => {
                    getUserResolve(user);
                    presented_baselines = await presentation;
                });

                it("returns baselines with author", () => {
                    expect(presented_baselines[0].author).toEqual(user);
                });
            });

            describe("when getUser() fail", () => {
                beforeEach(() => {
                    getUserReject("Exception reason");
                });

                it("throws exception", async () => {
                    try {
                        await presentation;
                        fail("No exception thrown");
                    } catch (exception) {
                        expect(exception).toEqual("Exception reason");
                    }
                });
            });
        });

        describe("when multiple baselines with same author", () => {
            beforeEach(() => {
                presentation = presentBaselines([{ author_id: 1 }, { author_id: 1 }]);
            });

            it("calls getUser once", () => expect(getUser).toHaveBeenCalledTimes(1));
        });

        describe("when multiple baselines with different authors", () => {
            beforeEach(() => {
                presentation = presentBaselines([{ author_id: 1 }, { author_id: 2 }]);
            });

            it("calls getUser for each author", () => expect(getUser).toHaveBeenCalledTimes(2));
        });
    });
});
