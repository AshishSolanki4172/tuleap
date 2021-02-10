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

namespace Tuleap\ProgramManagement\Program\PlanningConfiguration;

use Tuleap\ProgramManagement\ProgramTracker;
use Tuleap\ProgramManagement\Project;

/**
 * @psalm-immutable
 */
final class Planning
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var ProgramTracker
     */
    private $planning_tracker;
    /**
     * @var string
     */
    private $name;
    /**
     * @var array
     */
    private $backlog_tracker_ids = [];
    /**
     * @var Project
     */
    private $project_data;

    public function __construct(
        ProgramTracker $planning_tracker,
        int $id,
        string $name,
        array $backlog_tracker_ids,
        Project $project_data
    ) {
        $this->id                  = $id;
        $this->planning_tracker    = $planning_tracker;
        $this->name                = $name;
        $this->backlog_tracker_ids = $backlog_tracker_ids;
        $this->project_data        = $project_data;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getPlanningTracker(): ProgramTracker
    {
        return $this->planning_tracker;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlannableTrackerIds(): array
    {
        return $this->backlog_tracker_ids;
    }

    public function getProjectData(): Project
    {
        return $this->project_data;
    }
}
