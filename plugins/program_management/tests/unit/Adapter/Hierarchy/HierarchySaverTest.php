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

namespace Tuleap\ProgramManagement\Adapter\Program\Hierarchy;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\Hierarchy\Hierarchy;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

final class HierarchySaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItSavesHierarchy(): void
    {
        $dao   = \Mockery::mock(HierarchyDAO::class);
        $saver = new HierarchySaver($dao);

        $hierarchy = new Hierarchy(1, [30, 40]);

        $dao->shouldReceive('updateChildren')->with(1, [30, 40])->once();
        $saver->save($hierarchy);
    }
}
