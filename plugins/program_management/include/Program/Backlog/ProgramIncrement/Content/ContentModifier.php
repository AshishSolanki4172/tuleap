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

namespace Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\PlannedProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Plan\VerifyCanBePlannedInProgramIncrement;
use Tuleap\ProgramManagement\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Program\ProgramSearcher;

final class ContentModifier implements ModifyContent
{
    /**
     * @var VerifyPrioritizeFeaturesPermission
     */
    private $permission_verifier;
    /**
     * @var RetrieveProgramIncrement
     */
    private $program_increment_retriever;
    /**
     * @var ProgramSearcher
     */
    private $program_searcher;
    /**
     * @var VerifyCanBePlannedInProgramIncrement
     */
    private $can_be_planned_verifier;

    public function __construct(
        VerifyPrioritizeFeaturesPermission $permission_verifier,
        RetrieveProgramIncrement $program_increment_retriever,
        ProgramSearcher $program_searcher,
        VerifyCanBePlannedInProgramIncrement $can_be_planned_verifier
    ) {
        $this->permission_verifier         = $permission_verifier;
        $this->program_increment_retriever = $program_increment_retriever;
        $this->program_searcher            = $program_searcher;
        $this->can_be_planned_verifier     = $can_be_planned_verifier;
    }

    public function modifyContent(\PFUser $user, int $program_increment_id, ContentChange $content_change): void
    {
        $program_increment = $this->program_increment_retriever->retrieveProgramIncrement($program_increment_id, $user);
        $program           = $this->program_searcher->getProgramOfProgramIncrement($program_increment->getId());
        $has_permission    = $this->permission_verifier->canUserPrioritizeFeatures($program, $user);
        if (! $has_permission) {
            throw new NotAllowedToPrioritizeException((int) $user->getId(), $program_increment->getId());
        }
        if ($content_change->potential_feature_id_to_add !== null) {
            $this->planFeature($content_change->potential_feature_id_to_add, $program_increment);
        }
    }

    /**
     * @throws FeatureCannotBePlannedInProgramIncrementException
     */
    private function planFeature(
        int $potential_feature_id_to_add,
        PlannedProgramIncrement $program_increment
    ): void {
        $can_be_planned = $this->can_be_planned_verifier->canBePlannedInProgramIncrement(
            $potential_feature_id_to_add,
            $program_increment->getId()
        );
        if (! $can_be_planned) {
            throw new FeatureCannotBePlannedInProgramIncrementException(
                $potential_feature_id_to_add,
                $program_increment->getId()
            );
        }
    }
}
