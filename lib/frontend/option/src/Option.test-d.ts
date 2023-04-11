/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { describe, expectTypeOf, it } from "vitest";
import { Option } from "./Option";

type CustomType = {
    readonly property: string;
};

const CustomType = (property: string): CustomType => ({ property });

describe(`Option type`, () => {
    it(`apply() has correct type for its callback`, () => {
        const itCouldReturnNothing = (): Option<CustomType> => Option.nothing();

        expectTypeOf(
            itCouldReturnNothing().apply((received_value) => {
                expectTypeOf(received_value).toMatchTypeOf<CustomType>();
            })
        ).toBeVoid();
    });

    describe(`map()`, () => {
        it(`has correct type for its callback`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.nothing();

            const return_value = itCouldReturnNothing().map((received_value) => {
                expectTypeOf(received_value).toBeString();
                return "mapped";
            });

            expectTypeOf(return_value).toMatchTypeOf<Option<string>>();
        });

        it(`can map to a different type than the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<number> => Option.fromValue(123);

            const return_value = itCouldReturnNothing().map(() => {
                return new Set(["one", "two", "three"]);
            });

            expectTypeOf(return_value).toMatchTypeOf<Option<Set<string>>>();
        });
    });

    describe(`mapOr()`, () => {
        it(`has correct type for its callback`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.nothing();

            const return_value = itCouldReturnNothing().mapOr((received_value) => {
                expectTypeOf(received_value).toBeString();
                return "mapped";
            }, "default");

            expectTypeOf(return_value).toBeString();
        });

        it(`can map to a different type than the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.fromValue("33");

            const return_value = itCouldReturnNothing().mapOr((received_value) => {
                return Number.parseInt(received_value, 10) + 10;
            }, "default");
            expectTypeOf(return_value).toMatchTypeOf<number | string>();
        });

        it(`can return a different type of default value than the mapped type or the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.nothing();

            const return_value = itCouldReturnNothing().mapOr((received_value) => {
                return received_value === "argue" ? 994 : 271;
            }, CustomType("default"));
            expectTypeOf(return_value).toMatchTypeOf<number | CustomType>();
        });
    });

    describe(`unwrapOr()`, () => {
        it(`can return a different type of default value than the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<number> => Option.nothing();

            expectTypeOf(itCouldReturnNothing().unwrapOr(CustomType("default"))).toMatchTypeOf<
                number | CustomType
            >();
        });
    });
});
