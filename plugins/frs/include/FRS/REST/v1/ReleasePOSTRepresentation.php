<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\FRS\REST\v1;

class ReleasePOSTRepresentation
{
    /**
     * @var package_id {@type int} {@required true}
     */
    public $package_id;

    /**
     * @var name {@type string} {@required true}
     */
    public $name;

    /**
     * @var release_note {@type string} {@required false}
     */
    public $release_note;

    /**
     * @var changelog {@type string} {@required false}
     */
    public $changelog;

    /**
     * @var status {@type string} {@required false} {@choice active,hidden}
     */
    public $status;
}
