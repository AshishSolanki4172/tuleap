<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;


final class InvitationStatusUpdaterStub implements InvitationStatusUpdater
{
    private bool $has_been_marked_as_error = false;
    private bool $has_been_marked_as_sent  = false;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    public function markAsError(int $id): void
    {
        $this->has_been_marked_as_error = true;
    }

    public function markAsSent(int $id): void
    {
        $this->has_been_marked_as_sent = true;
    }

    public function hasBeenMarkedAsError(): bool
    {
        return $this->has_been_marked_as_error;
    }

    public function hasBeenMarkedAsSent(): bool
    {
        return $this->has_been_marked_as_sent;
    }
}
