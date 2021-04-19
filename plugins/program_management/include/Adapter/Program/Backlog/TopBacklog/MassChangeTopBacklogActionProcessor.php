<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Program\Plan\BuildProgram;

class MassChangeTopBacklogActionProcessor
{
    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;

    public function __construct(BuildProgram $build_program, TopBacklogChangeProcessor $top_backlog_change_processor)
    {
        $this->build_program                = $build_program;
        $this->top_backlog_change_processor = $top_backlog_change_processor;
    }

    public function processMassChangeAction(
        MassChangeTopBacklogSourceInformation $source_information
    ): void {
        switch ($source_information->action) {
            case 'add':
                $top_backlog_change = new TopBacklogChange($source_information->masschange_aids, [], false, null);
                break;
            case 'remove':
                $top_backlog_change = new TopBacklogChange([], $source_information->masschange_aids, false, null);
                break;
            default:
                return;
        }

        $user = $source_information->user;
        try {
            $program = $this->build_program->buildExistingProgramProject($source_information->project_id, $user);
        } catch (ProgramAccessException | ProjectIsNotAProgramException $e) {
            return;
        }

        $this->top_backlog_change_processor->processTopBacklogChangeForAProgram($program, $top_backlog_change, $user);
    }
}
