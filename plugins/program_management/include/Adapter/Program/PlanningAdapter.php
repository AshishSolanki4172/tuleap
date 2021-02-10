<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Program\BuildPlanning;
use Tuleap\ProgramManagement\Program\PlanningConfiguration\Planning;
use Tuleap\ProgramManagement\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\ProgramTracker;

final class PlanningAdapter implements BuildPlanning
{
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;

    public function __construct(\PlanningFactory $planning_factory)
    {
        $this->planning_factory = $planning_factory;
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     */
    public function buildRootPlanning(\PFUser $user, int $project_id): Planning
    {
        $root_planning = $this->planning_factory->getRootPlanning(
            $user,
            $project_id
        );

        if (! $root_planning) {
            throw new TopPlanningNotFoundInProjectException($project_id);
        }


        if ($root_planning->getPlanningTracker() instanceof \NullTracker) {
            throw new PlanningHasNoProgramIncrementException($root_planning->getId());
        }
        $project_data = ProjectAdapter::build($root_planning->getPlanningTracker()->getProject());

        return new Planning(
            new ProgramTracker($root_planning->getPlanningTracker()),
            $root_planning->getId(),
            $root_planning->getName(),
            $root_planning->getBacklogTrackersIds(),
            $project_data
        );
    }
}
