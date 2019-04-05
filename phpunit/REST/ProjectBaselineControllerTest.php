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

namespace Tuleap\Baseline\REST;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\BaselinesPage;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\ProjectRepository;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\GlobalLanguageMock;

class ProjectBaselineControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var ProjectBaselineController */
    private $controller;

    /** @var CurrentUserProvider|MockInterface */
    private $current_user_provider;

    /** @var BaselineService|MockInterface */
    private $baseline_service;

    /** @var ProjectRepository */
    private $project_repository;

    /** @var Project|MockInterface */
    private $a_project;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class)->shouldIgnoreMissing();
        $this->baseline_service      = Mockery::mock(BaselineService::class);
        $this->project_repository    = Mockery::mock(ProjectRepository::class);

        $this->controller = new ProjectBaselineController(
            $this->current_user_provider,
            $this->baseline_service,
            $this->project_repository
        );
    }

    /** @before */
    public function createEntities()
    {
        $this->a_project = Mockery::mock(Project::class);
    }

    public function testGet()
    {
        $current_user = new PFUser();
        $this->current_user_provider
            ->shouldReceive('getUser')
            ->andReturn($current_user);

        $this->project_repository
            ->shouldReceive('findById')
            ->with($current_user, 102)
            ->andReturn($this->a_project);

        $this->baseline_service
            ->shouldReceive('findByProject')
            ->with($current_user, $this->a_project, 10, 7)
            ->andReturn(
                new BaselinesPage(
                    [BaselineFactory::one()->build()],
                    10,
                    7,
                    233
                )
            );

        $representation = $this->controller->get(102, 10, 7);

        $this->assertEquals(1, count($representation->baselines));
        $this->assertEquals(233, $representation->total_count);
    }

    public function testGetThrows404WhenNoProjectFound()
    {
        $this->expectException(NotFoundRestException::class);

        $current_user = new PFUser();
        $this->current_user_provider
            ->shouldReceive('getUser')
            ->andReturn($current_user);

        $this->project_repository
            ->shouldReceive('findById')
            ->with($current_user, 102)
            ->andReturn(null);

        $this->controller->get(102, 10, 0);
    }

    public function testGetThrows403WhenNotAuthorized()
    {
        $this->expectException(ForbiddenRestException::class);

        $this->project_repository
            ->shouldReceive('findById')
            ->andReturn($this->a_project);

        $this->baseline_service
            ->shouldReceive('findByProject')
            ->andThrow(new NotAuthorizedException('not authorized'));

        $this->controller->get(102, 10, 0);
        $this->assertTrue(false);
    }
}
