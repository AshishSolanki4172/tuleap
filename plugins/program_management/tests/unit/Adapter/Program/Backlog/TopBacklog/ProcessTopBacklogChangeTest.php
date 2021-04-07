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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class ProcessTopBacklogChangeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PrioritizeFeaturesPermissionVerifier
     */
    private $permissions_verifier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsExplicitTopBacklogDAO
     */
    private $dao;
    /**
     * @var ProcessTopBacklogChange
     */
    private $process_top_backlog_change;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramIncrementsDAO
     */
    private $program_increment_dao;

    protected function setUp(): void
    {
        $this->artifact_factory      = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->permissions_verifier  = \Mockery::mock(PrioritizeFeaturesPermissionVerifier::class);
        $this->dao                   = \Mockery::mock(ArtifactsExplicitTopBacklogDAO::class);
        $this->artifact_link_updater = \Mockery::mock(ArtifactLinkUpdater::class);
        $this->program_increment_dao = \Mockery::mock(ProgramIncrementsDAO::class);

        $this->process_top_backlog_change = new ProcessTopBacklogChange(
            $this->artifact_factory,
            $this->permissions_verifier,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->artifact_link_updater,
            $this->program_increment_dao
        );
    }

    public function testAddAndRemoveOnlyArtifactsUserCanView(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $artifact_741 = \Mockery::mock(Artifact::class);
        $artifact_742 = \Mockery::mock(Artifact::class);
        $tracker      = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(102);
        $artifact_741->shouldReceive('getTracker')->andReturn($tracker);
        $artifact_742->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 741)->andReturn($artifact_741);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 742)->andReturn($artifact_742);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 789)->andReturn(null);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 790)->andReturn(null);

        $this->dao->shouldReceive('removeArtifactsFromExplicitTopBacklog')->with([741])->once();
        $this->dao->shouldReceive('addArtifactsToTheExplicitTopBacklog')->with([742])->once();

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            new Program(102),
            new TopBacklogChange([742, 790], [741, 789], false),
            $user
        );
    }

    public function testAddAndRemoveOnlyArtifactThatArePartOfTheRequestedProgram(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $artifact = \Mockery::mock(Artifact::class);
        $tracker  = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(666);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn($artifact);

        $this->dao->shouldNotReceive('removeArtifactsFromExplicitTopBacklog');

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            new Program(102),
            new TopBacklogChange([964], [963], false),
            $user
        );
    }

    public function testAddFeatureInTopBacklogAndRemoveLinkToProgramIncrement(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true)->once();
        $user = UserTestBuilder::aUser()->build();

        $feature = \Mockery::mock(Artifact::class);
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(102)->once();
        $feature->shouldReceive('getTracker')->andReturn($tracker)->once();
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 964)->andReturn($feature)->once();

        $this->program_increment_dao->shouldReceive("getProgramIncrementsLinkToFeatureId")->with(964)->once()->andReturn([["id" => 63]]);
        $program_increment = \Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(63)->andReturn($program_increment)->once();

        $this->dao->shouldReceive('addArtifactsToTheExplicitTopBacklog')->once();
        $this->artifact_link_updater->shouldReceive("updateArtifactLinks")->once()->with($user, $program_increment, [], [964], "");

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            new Program(102),
            new TopBacklogChange([964], [], true),
            $user
        );
    }

    public function testUserThatCannotPrioritizeFeaturesCannotAskForATopBacklogChange(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(false);

        $this->dao->shouldNotReceive('removeArtifactsFromExplicitTopBacklog');

        $this->expectException(CannotManipulateTopBacklog::class);
        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            new Program(102),
            new TopBacklogChange([], [403], false),
            UserTestBuilder::aUser()->build()
        );
    }
}
