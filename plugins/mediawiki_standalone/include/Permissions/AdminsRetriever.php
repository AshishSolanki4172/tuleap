<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions;

final class AdminsRetriever
{
    public function __construct(private ISearchByProjectAndPermission $dao)
    {
    }

    /**
     * @return int[]
     */
    public function getAdminsUgroupIds(\Project $project): array
    {
        $admins = array_column($this->dao->searchByProjectAndPermission($project, new PermissionAdmin()), 'ugroup_id');

        if (! in_array(\ProjectUGroup::PROJECT_ADMIN, $admins, true)) {
            array_unshift($admins, \ProjectUGroup::PROJECT_ADMIN);
        }

        return $admins;
    }
}
