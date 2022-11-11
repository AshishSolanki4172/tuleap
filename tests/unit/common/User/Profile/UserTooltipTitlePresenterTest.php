<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\User\Profile;

use Tuleap\Test\Builders\UserTestBuilder;

final class UserTooltipTitlePresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const REAL_NAME  = 'Donetta Humphrys';
    private const USERNAME   = 'dhumphrys';
    private const AVATAR_URL = '/avatar/9ab958.png';

    public function testItBuildsFromUserWithAvatar(): void
    {
        $presenter = UserTooltipTitlePresenter::fromUser(
            UserTestBuilder::aUser()
                ->withAvatarUrl(self::AVATAR_URL)
                ->withRealName(self::REAL_NAME)
                ->withUserName(self::USERNAME)
                ->build()
        );

        self::assertSame(self::REAL_NAME, $presenter->real_name);
        self::assertSame(self::USERNAME, $presenter->username);
        self::assertSame(self::AVATAR_URL, $presenter->avatar_url);
    }
}
