<?php
/**
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

declare(strict_types=1);

namespace Tuleap\Option;

/**
 * PHPUnit will always succeed these tests. They are meant to raise errors in Psalm instead.
 * Psalm will raise errors here if breaking changes are introduced on type annotations.
 */
final class TypeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testMapOrCanMapToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::fromValue('33');

        $test = new class {
            public int $expectation;
        };

        $test->expectation = $option->mapOr(
            static fn(string $value) => (int) $value + 10,
            99
        );
        self::assertTrue($option->isValue());
        self::assertSame(43, $test->expectation);
    }

    public function testMapOrCanReturnADifferentTypeThanTheMappedTypeOrTheInitialOption(): void
    {
        $option = Option::nothing(\Psl\Type\string());

        $test = new class {
            public int|CustomValueType $expectation;
        };

        $test->expectation = $option->mapOr(
            static fn(string $value) => (int) $value + 10,
            new CustomValueType(21, 'pick')
        );
        self::assertInstanceOf(CustomValueType::class, $test->expectation);
    }

    public function testUnwrapOrCanDefaultToADifferentTypeThanTheInitialOption(): void
    {
        $option = Option::nothing(\Psl\Type\string());

        $test = new class {
            public int|string $expectation;
        };

        $test->expectation = $option->unwrapOr(101);
        self::assertSame(101, $test->expectation);
    }
}
