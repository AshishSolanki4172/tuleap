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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use PFUser;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\RetrieveProgramIncrements;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

final class ProgramIncrementsRetriever implements RetrieveProgramIncrements
{
    /**
     * @var ProgramIncrementsDAO
     */
    private $program_increments_dao;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var TimeframeBuilder
     */
    private $timeframe_builder;

    public function __construct(
        ProgramIncrementsDAO $program_increments_dao,
        \Tracker_ArtifactFactory $artifact_factory,
        TimeframeBuilder $timeframe_builder
    ) {
        $this->program_increments_dao = $program_increments_dao;
        $this->artifact_factory       = $artifact_factory;
        $this->timeframe_builder      = $timeframe_builder;
    }

    /**
     * @return ProgramIncrement[]
     */
    public function retrieveOpenProgramIncrements(Program $program, \PFUser $user): array
    {
        $program_increment_rows      = $this->program_increments_dao->searchOpenProgramIncrements($program->getId());
        $program_increment_artifacts = [];

        foreach ($program_increment_rows as $program_increment_row) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $program_increment_row['id']);
            if ($artifact !== null) {
                $program_increment_artifacts[] = $artifact;
            }
        }

        $program_increments = [];
        foreach ($program_increment_artifacts as $program_increment_artifact) {
            $program_increment = $this->getProgramIncrementFromArtifact($user, $program_increment_artifact);
            if ($program_increment !== null) {
                $program_increments[] = $program_increment;
            }
        }

        return $program_increments;
    }

    private function getProgramIncrementFromArtifact(\PFUser $user, Artifact $program_increment_artifact): ?ProgramIncrement
    {
        $title = $program_increment_artifact->getTitle();
        if ($title === null) {
            return null;
        }

        $status       = null;
        $status_field = $program_increment_artifact->getTracker()->getStatusField();
        if ($status_field !== null && $status_field->userCanRead($user)) {
            $status = $program_increment_artifact->getStatus();
        }

        $time_period = $this->timeframe_builder->buildTimePeriodWithoutWeekendForArtifactForREST($program_increment_artifact, $user);

        return new ProgramIncrement(
            $program_increment_artifact->getId(),
            $title,
            $program_increment_artifact->userCanUpdate($user),
            $this->userCanPlan($program_increment_artifact, $user),
            $status,
            $time_period->getStartDate(),
            $time_period->getEndDate()
        );
    }

    private function userCanPlan(Artifact $program_increment_artifact, PFUser $user): bool
    {
        if (! $program_increment_artifact->userCanUpdate($user)) {
            return false;
        }

        $artifact_link = $program_increment_artifact->getAnArtifactLinkField($user);
        if (! $artifact_link) {
            return false;
        }

        return $artifact_link->userCanUpdate($user);
    }
}
