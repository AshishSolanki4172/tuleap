<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);


namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanPlanInProgramIncrement;

final class UserCanPlanInProgramIncrementVerifier implements VerifyUserCanPlanInProgramIncrement
{
    public function __construct(private RetrieveFullArtifact $artifact_retriever, private RetrieveUser $retrieve_user)
    {
    }

    public function userCanPlan(
        ProgramIncrementIdentifier $program_increment_identifier,
        UserIdentifier $user_identifier
    ): bool {
        $program_increment_artifact = $this->artifact_retriever->getNonNullArtifact($program_increment_identifier);

        $user = $this->retrieve_user->getUserWithId($user_identifier);
        if (! $program_increment_artifact->userCanUpdate($user)) {
            return false;
        }

        $artifact_link = $program_increment_artifact->getAnArtifactLinkField($user);
        if (! $artifact_link) {
            return false;
        }

        return $artifact_link->userCanUpdate($user);
    }

    public function userCanPlanAndPrioritize(
        ProgramIncrementIdentifier $program_increment_identifier,
        UserCanPrioritize $user_identifier
    ): bool {
        return $this->userCanPlan($program_increment_identifier, $user_identifier);
    }
}
