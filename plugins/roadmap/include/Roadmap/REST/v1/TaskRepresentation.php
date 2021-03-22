<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

/**
 * @psalm-immutable
 */
final class TaskRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $xref;
    /**
     * @var string
     */
    public $html_url;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $color_name;

    public function __construct(int $id, string $xref, string $html_url, string $title, string $color_name)
    {
        $this->id         = $id;
        $this->xref       = $xref;
        $this->html_url   = $html_url;
        $this->title      = $title;
        $this->color_name = $color_name;
    }
}
