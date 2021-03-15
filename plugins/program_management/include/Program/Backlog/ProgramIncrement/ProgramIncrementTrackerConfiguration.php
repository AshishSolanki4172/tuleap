<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement;

/**
 * @psalm-immutable
 */
final class ProgramIncrementTrackerConfiguration
{
    /**
     * @var bool
     */
    public $can_create_program_increment;
    /**
     * @var int
     */
    private $program_increment_tracker_id;
    /**
     * @var int | null
     */
    private $artifact_link_field_id;

    public function __construct(
        int $program_increment_tracker_id,
        bool $can_create_program_increment,
        ?int $artifact_link_field_id
    ) {
        $this->can_create_program_increment = $can_create_program_increment;
        $this->program_increment_tracker_id = $program_increment_tracker_id;
        $this->artifact_link_field_id       = $artifact_link_field_id;
    }

    public function canCreateProgramIncrement(): bool
    {
        return $this->can_create_program_increment;
    }

    public function getProgramIncrementTrackerId(): int
    {
        return $this->program_increment_tracker_id;
    }

    public function getArtifactLinkFieldId(): ?int
    {
        return $this->artifact_link_field_id;
    }
}
