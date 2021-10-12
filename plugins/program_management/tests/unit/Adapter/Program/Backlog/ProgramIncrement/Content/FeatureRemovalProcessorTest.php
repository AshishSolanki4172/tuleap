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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeatureRemoval;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeatureException;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class FeatureRemovalProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FeatureRemovalProcessor $processor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProgramIncrementsDAO
     */
    private $program_increments_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ArtifactLinkUpdater
     */
    private $artifact_link_updater;

    protected function setUp(): void
    {
        $this->program_increments_dao = $this->createMock(ProgramIncrementsDAO::class);
        $this->artifact_factory       = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->artifact_link_updater  = $this->createMock(ArtifactLinkUpdater::class);
        $this->processor              = new FeatureRemovalProcessor(
            $this->program_increments_dao,
            $this->artifact_factory,
            $this->artifact_link_updater,
            RetrieveUserStub::withUser(UserTestBuilder::aUser()->build())
        );
    }

    public function testWhenThereAreNoProgramIncrementsLinkingTheFeatureItDoesNothing(): void
    {
        $this->program_increments_dao->expects(self::once())
            ->method('getProgramIncrementsLinkToFeatureId')
            ->willReturn([]);

        $feature_removal = $this->buildFeatureRemoval();
        $this->processor->removeFromAllProgramIncrements($feature_removal);

        $this->artifact_link_updater->expects(self::never())->method('updateArtifactLinks');
    }

    public function testItSkipsNonExistentProgramIncrements(): void
    {
        $program_increment_ids = [['id' => 404], ['id' => 405]];
        $this->program_increments_dao->expects(self::once())
            ->method('getProgramIncrementsLinkToFeatureId')
            ->willReturn($program_increment_ids);

        $program_increment_artifact = new Artifact(405, 7, 101, 1234567890, false);

        $this->artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [404, null],
                [405, $program_increment_artifact],
            ]);

        $feature_removal = $this->buildFeatureRemoval();
        $this->artifact_link_updater->expects(self::once())
            ->method('updateArtifactLinks')
            ->with(
                self::isInstanceOf(\PFUser::class),
                $program_increment_artifact,
                [],
                [$feature_removal->feature_id],
                \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
            );

        $this->processor->removeFromAllProgramIncrements($feature_removal);
    }

    public function dataProviderExceptions(): array
    {
        return [
            'it wraps Tracker_Exception'                    => [new \Tracker_Exception()],
            'it wraps Tracker_NoArtifactLinkFieldException' => [new \Tracker_NoArtifactLinkFieldException()],
        ];
    }

    /**
     * @dataProvider dataProviderExceptions
     */
    public function testItWrapsExceptions(\Throwable $exception): void
    {
        $program_increment_ids = [['id' => 25]];
        $this->program_increments_dao->method('getProgramIncrementsLinkToFeatureId')
            ->willReturn($program_increment_ids);

        $this->artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [25, new Artifact(25, 7, 101, 1234567890, false)],
                [98, new Artifact(98, 7, 101, 1234567890, false)],
            ]);

        $feature_removal = $this->buildFeatureRemoval();
        $this->artifact_link_updater->method('updateArtifactLinks')->willThrowException($exception);

        $this->expectException(RemoveFeatureException::class);
        $this->processor->removeFromAllProgramIncrements($feature_removal);
    }

    public function testItUpdatesArtifactLinksToRemoveFeatureFromAllProgramIncrements(): void
    {
        $program_increment_ids = [['id' => 25], ['id' => 98]];
        $this->program_increments_dao->method('getProgramIncrementsLinkToFeatureId')
            ->willReturn($program_increment_ids);

        $this->artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [25, new Artifact(25, 7, 101, 1234567890, false)],
                [98, new Artifact(98, 7, 101, 1234567890, false)],
            ]);

        $feature_removal = $this->buildFeatureRemoval();
        $this->artifact_link_updater->expects(self::exactly(2))->method('updateArtifactLinks');

        $this->processor->removeFromAllProgramIncrements($feature_removal);
    }

    private function buildFeatureRemoval(): FeatureRemoval
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $program         = ProgramIdentifierBuilder::buildWithId(110);
        $feature         = FeatureIdentifierBuilder::build(76, 110);

        return FeatureRemoval::fromFeature(
            VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
            $feature,
            UserCanPrioritize::fromUser(VerifyPrioritizeFeaturesPermissionStub::canPrioritize(), $user_identifier, $program, null)
        );
    }
}
