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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\ScaledAgile\Program\Plan\BuildProgramUserGroup;
use Tuleap\ScaledAgile\Program\Plan\InvalidProgramUserGroup;
use Tuleap\ScaledAgile\Program\Plan\ProgramUserGroup;
use Tuleap\ScaledAgile\Program\Program;

final class ProgramUserGroupBuildAdapter implements BuildProgramUserGroup
{
    /**
     * @var UserGroupRetriever
     */
    private $user_group_retriever;

    public function __construct(UserGroupRetriever $user_group_retriever)
    {
        $this->user_group_retriever = $user_group_retriever;
    }

    /**
     * @param non-empty-list<string> $raw_user_group_ids
     * @return non-empty-list<ProgramUserGroup>
     * @throws InvalidProgramUserGroup
     */
    public function buildProgramUserGroups(Program $program, array $raw_user_group_ids): array
    {
        $program_user_groups = [];

        foreach ($raw_user_group_ids as $raw_user_group_id) {
            $project_user_group = $this->user_group_retriever->getExistingUserGroup($raw_user_group_id);

            if ((int) $project_user_group->getProjectId() !== $program->getId()) {
                throw new ProgramUserGroupDoesNotExistException($raw_user_group_id);
            }

            $program_user_groups[] = new ProgramUserGroup($program, $project_user_group->getId());
        }

        return $program_user_groups;
    }
}
