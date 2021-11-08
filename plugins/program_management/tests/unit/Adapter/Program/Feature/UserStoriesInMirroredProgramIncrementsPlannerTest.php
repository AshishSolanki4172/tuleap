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

namespace Adapter\Program\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchArtifactsLinks;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ContentStoreStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUnlinkedUserStoriesOfMirroredProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchArtifactsLinksStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class UserStoriesInMirroredProgramIncrementsPlannerTest extends TestCase
{
    private const MIRRORED_TIMEBOX_ID    = 666;
    private const USER_STORY_ID          = 1234;
    private const FEATURE_ID             = 101;
    private const TEAM_ID                = 172;
    private const ARTIFACT_LINK_FIELD_ID = 1;
    private SearchArtifactsLinks $search_artifacts_links;
    private ProgramIncrementChanged $program_increment_changed;
    private ContentStore $content_dao;
    /**
     * @var MockObject&Artifact
     */
    private $milestone;

    protected function setUp(): void
    {
        $this->search_artifacts_links = SearchArtifactsLinksStub::withoutArtifactLinks();
        $this->content_dao            = ContentStoreStub::withoutRows();

        $update                          = ProgramIncrementUpdateBuilder::build();
        $this->program_increment_changed = ProgramIncrementChanged::fromUpdate($update);

        $this->milestone = $this->createMock(Artifact::class);
    }


    private function getPlanner(): UserStoriesInMirroredProgramIncrementsPlanner
    {
        return new UserStoriesInMirroredProgramIncrementsPlanner(
            new DBTransactionExecutorPassthrough(),
            $this->search_artifacts_links,
            RetrieveFullArtifactStub::withArtifact($this->milestone),
            SearchMirroredTimeboxesStub::withIds(self::MIRRORED_TIMEBOX_ID),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $this->content_dao,
            new NullLogger(),
            RetrieveUserStub::withGenericUser(),
            RetrieveUnlinkedUserStoriesOfMirroredProgramIncrementStub::buildEmptyUserStories()
        );
    }

    public function testItAddLinksToMirroredMilestones(): void
    {
        $raw_link                     = ['id' => self::USER_STORY_ID, 'project_id' => self::TEAM_ID];
        $this->content_dao            = ContentStoreStub::withRows([['artifact_id' => self::FEATURE_ID, "tracker_name" => 'tracker', "artifact_title" => 'My artifact', "field_title_id" => 1]]);
        $this->search_artifacts_links = SearchArtifactsLinksStub::withArtifactLinks([$raw_link]);

        $this->milestone->method('getId')->willReturn(self::MIRRORED_TIMEBOX_ID);
        $team_project = ProjectTestBuilder::aProject()->withId(self::TEAM_ID)->build();
        $this->milestone->method('getTracker')->willReturn(
            TrackerTestBuilder::aTracker()->withProject($team_project)->build()
        );
        $field_artifact_link = $this->createStub(Tracker_FormElement_Field_ArtifactLink::class);
        $field_artifact_link->method('getId')->willReturn(self::ARTIFACT_LINK_FIELD_ID);
        $this->milestone->method('getAnArtifactLinkField')->willReturn($field_artifact_link);

        $fields_data = new FieldData(
            [FeatureChange::fromRaw($raw_link)],
            [],
            self::ARTIFACT_LINK_FIELD_ID
        );

        $this->milestone->expects(self::once())
                  ->method('createNewChangeset')->with(
                      $fields_data->getFieldDataForChangesetCreationFormat(self::TEAM_ID),
                      '',
                      self::isInstanceOf(\PFUser::class)
                  );

        $this->getPlanner()->plan($this->program_increment_changed);
    }

    public function testItDoesNothingWhenArtifactLinkIsNotFound(): void
    {
        $this->content_dao            = ContentStoreStub::withRows([['artifact_id' => self::FEATURE_ID, "tracker_name" => 'tracker', "artifact_title" => 'My artifact', "field_title_id" => 1]]);
        $this->search_artifacts_links = SearchArtifactsLinksStub::withArtifactLinks([['id' => self::USER_STORY_ID, 'project_id' => self::TEAM_ID]]);

        $this->milestone->method('getAnArtifactLinkField')->willReturn(null);

        $this->milestone->expects(self::never())->method('createNewChangeset');
        $this->getPlanner()->plan($this->program_increment_changed);
    }

    public function testItDoesNotAddUserStoryIfUserStoryIsNotInProject(): void
    {
        $other_project_id  = 122;
        $this->content_dao = ContentStoreStub::withRows([['artifact_id' => self::FEATURE_ID, "tracker_name" => 'tracker', "artifact_title" => 'My artifact', "field_title_id" => 1]]);

        $raw_link = ['id' => self::USER_STORY_ID, 'project_id' => $other_project_id];

        $this->search_artifacts_links = SearchArtifactsLinksStub::withArtifactLinks([$raw_link]);

        $this->milestone->method('getId')->willReturn(self::MIRRORED_TIMEBOX_ID);
        $team_project = ProjectTestBuilder::aProject()->withId(self::TEAM_ID)->build();
        $this->milestone->method('getTracker')->willReturn(
            TrackerTestBuilder::aTracker()->withProject($team_project)->build()
        );

        $field_artifact_link = $this->createStub(Tracker_FormElement_Field_ArtifactLink::class);
        $field_artifact_link->method('getId')->willReturn(self::ARTIFACT_LINK_FIELD_ID);
        $this->milestone->method('getAnArtifactLinkField')->willReturn($field_artifact_link);

        $fields_data = new FieldData(
            [],
            [],
            self::ARTIFACT_LINK_FIELD_ID
        );

        $this->milestone->expects(self::once())
            ->method('createNewChangeset')
            ->with(
                $fields_data->getFieldDataForChangesetCreationFormat($other_project_id),
                '',
                self::isInstanceOf(\PFUser::class)
            );

        $this->getPlanner()->plan($this->program_increment_changed);
    }
}
