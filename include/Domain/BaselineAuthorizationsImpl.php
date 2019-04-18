<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline;

use PFUser;
use Project;

class BaselineAuthorizationsImpl implements BaselineAuthorizations
{
    /** @var RoleAssignmentRepository */
    private $role_assignment_repository;

    public function __construct(RoleAssignmentRepository $role_assignment_repository)
    {
        $this->role_assignment_repository = $role_assignment_repository;
    }

    public function canCreateBaseline(PFUser $current_user, TransientBaseline $baseline): bool
    {
        $project = $baseline->getProject();
        return $this->canUserAdministrateBaselineOnProject($current_user, $project);
    }

    public function canDeleteBaseline(PFUser $current_user, Baseline $baseline): bool
    {
        $project = $baseline->getProject();
        return $this->canUserAdministrateBaselineOnProject($current_user, $project);
    }

    public function canReadBaseline(PFUser $current_user, Baseline $baseline): bool
    {
        $project = $baseline->getProject();
        return $this->canUserReadBaselineOnProject($current_user, $project);
    }

    public function canReadBaselinesOnProject(PFUser $current_user, Project $project): bool
    {
        return $this->canUserReadBaselineOnProject($current_user, $project);
    }

    private function canUserAdministrateBaselineOnProject(PFUser $user, Project $project): bool
    {
        if ($this->isUserAdminOnProject($user, $project)) {
            return true;
        }
        return $this->hasUserRoleOnProject($user, Role::ADMIN, $project);
    }

    private function canUserReadBaselineOnProject(PFUser $user, Project $project): bool
    {
        if ($this->isUserAdminOnProject($user, $project)) {
            return true;
        }
        return $this->canUserAdministrateBaselineOnProject($user, $project)
            || $this->hasUserRoleOnProject($user, Role::READER, $project);
    }

    private function hasUserRoleOnProject(PFUser $user, string $role, Project $project): bool
    {
        $assignments = $this->role_assignment_repository->findByProjectAndRole($project, $role);
        foreach ($assignments as $assignment) {
            if ($user->isMemberOfUGroup($assignment->getUserGroupId(), $project->getID())) {
                return true;
            }
        }
        return false;
    }

    private function isUserAdminOnProject(PFUser $user, Project $project): bool
    {
        return $user->isSuperUser() || $user->isAdmin($project->getID());
    }
}
