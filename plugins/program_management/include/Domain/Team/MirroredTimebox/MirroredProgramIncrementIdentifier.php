<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold the identifier of an Artifact of the Mirrored Program Increment Tracker of a Team.
 * A Mirrored Program Increment Tracker is the Milestone Tracker of the root-level planning of a Team Project.
 * For example: a Team has a root-level AgileDashboard planning. In it, we can plan things in Releases.
 * The Mirrored Program Increment Tracker is then the Releases Tracker.
 * @psalm-immutable
 */
final class MirroredProgramIncrementIdentifier implements MirroredTimeboxIdentifier
{
    private function __construct(private int $id)
    {
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromProgramIncrement(
        SearchMirroredTimeboxes $timebox_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user
    ): array {
        $ids               = $timebox_searcher->searchMirroredTimeboxes($program_increment);
        $valid_identifiers = [];
        foreach ($ids as $id) {
            if ($visibility_verifier->isVisible($id, $user)) {
                $valid_identifiers[] = new self($id);
            }
        }
        return $valid_identifiers;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
