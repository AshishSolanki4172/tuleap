<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

class CloneURLPresenter
{
    /** @var string */
    public $url;
    /** @var string */
    public $label;
    /** @var bool */
    public $is_selected;
    /** @var bool */
    public $is_read_only;

    /**
     * @param string $url
     * @param string $label
     * @param bool   $is_selected
     * @param bool   $is_read_only
     */
    public function __construct($url, $label, $is_selected, $is_read_only)
    {
        $this->url          = $url;
        $this->label        = $label;
        $this->is_selected  = $is_selected;
        $this->is_read_only = $is_read_only;
    }
}
