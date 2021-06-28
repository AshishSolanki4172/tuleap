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

namespace Tuleap\Project\Sidebar;

/**
 * @psalm-immutable
 */
final class LinkedProjectPresenter
{
    public string $public_name;
    public string $uri;

    private function __construct(string $public_name, string $uri)
    {
        $this->public_name = $public_name;
        $this->uri         = $uri;
    }

    public static function fromLinkedProject(LinkedProject $linked_project): self
    {
        return new self($linked_project->public_name, $linked_project->uri);
    }
}
