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

namespace Tuleap\Layout;

use Tuleap\Layout\HeaderConfiguration\InProjectWithoutSidebar;

final class HeaderConfigurationBuilder
{
    /**
     * @var string[]
     */
    private array $body_class                                    = [];
    private ?InProjectWithoutSidebar $in_project_without_sidebar = null;

    private function __construct(private string $title)
    {
    }

    public static function get(string $title): self
    {
        return new self($title);
    }

    /**
     * @param string[] $body_class
     */
    public function withBodyClass(array $body_class): self
    {
        $this->body_class = $body_class;

        return $this;
    }

    public function inProjectWithoutSidebar(InProjectWithoutSidebar\BackToLinkPresenter $back_to_link): self
    {
        $this->in_project_without_sidebar = new InProjectWithoutSidebar($back_to_link);

        return $this;
    }

    public function build(): HeaderConfiguration
    {
        return new HeaderConfiguration($this->title, $this->in_project_without_sidebar, $this->body_class);
    }
}
