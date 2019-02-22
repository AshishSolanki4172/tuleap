<?php
/**
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

namespace Tuleap\Baseline\Stub;

use DateTime;
use Tuleap\Baseline\Clock;
use Tuleap\Baseline\Support\DateTimeFactory;

/**
 * A clock where time is frozen. Useful for tests.
 */
class FrozenClock implements Clock
{
    /** @var DateTime */
    private $now;

    public function __construct()
    {
        $this->now = DateTimeFactory::one();
    }

    public function setNow(DateTime $now): void
    {
        $this->now = $now;
    }

    public function now(): DateTime
    {
        return $this->now;
    }
}
