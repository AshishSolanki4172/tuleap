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

type IOption<TypeOfValue> = {
    apply(fn: (value: TypeOfValue) => void): void;
};

type Some<TypeOfValue> = IOption<TypeOfValue> & {
    mapOr: <TypeOfMapped, TypeOfDefault>(
        fn: (value: TypeOfValue) => TypeOfMapped,
        default_value: TypeOfDefault
    ) => TypeOfMapped;
    unwrapOr: <TypeOfDefault>(default_value: TypeOfDefault) => TypeOfValue;
    isValue(): true;
    isNothing(): false;
};

type None<TypeOfValue> = IOption<TypeOfValue> & {
    mapOr: <TypeOfMapped, TypeOfDefault>(
        fn: (value: TypeOfValue) => TypeOfMapped,
        default_value: TypeOfDefault
    ) => TypeOfDefault;
    unwrapOr: <TypeOfDefault>(default_value: TypeOfDefault) => TypeOfDefault;
    isValue(): false;
    isNothing(): true;
};

export type Option<TypeOfValue> = Some<TypeOfValue> | None<TypeOfValue>;

export const Option = {
    fromValue: <TypeOfValue>(value: TypeOfValue): Some<TypeOfValue> => ({
        apply: (fn) => fn(value),
        mapOr: (fn) => fn(value),
        unwrapOr: () => value,
        isNothing: () => false,
        isValue: () => true,
    }),

    nothing: <TypeOfValue>(): None<TypeOfValue> => ({
        apply(): void {
            // Do nothing
        },
        mapOr: (fn, default_value) => default_value,
        unwrapOr: (default_value) => default_value,
        isNothing: () => true,
        isValue: () => false,
    }),
};
