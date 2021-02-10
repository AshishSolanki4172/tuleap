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

namespace Tuleap\ProgramManagement\Program\Backlog\CreationCheck;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_Text;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ProgramManagement\Program\ProgramStore;
use Tuleap\ProgramManagement\ProgramTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementArtifactCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|BuildSynchronizedFields
     */
    private $fields_adapter;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|\PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|ProgramDao
     */
    private $program_store;

    /**
     * @var \Tuleap\ProgramManagement\Project
     */
    private $project_data;

    /**
     * @var ProgramIncrementArtifactCreatorChecker
     */
    private $checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder
     */
    private $field_collection_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CheckSemantic
     */
    private $semantic_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CheckRequiredField
     */
    private $required_field_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CheckWorkflow
     */
    private $workflow_checker;
    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program_store   = Mockery::mock(ProgramStore::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $project_data_adapter  = new ProjectAdapter($this->project_manager);

        $projects_collection_builder = new TeamProjectsCollectionBuilder(
            $this->program_store,
            $project_data_adapter
        );

        $this->planning_factory = Mockery::mock(\PlanningFactory::class);
        $planning_adapter       = new PlanningAdapter($this->planning_factory);
        $trackers_builder       = new TrackerCollectionFactory($planning_adapter);

        $this->fields_adapter           = Mockery::mock(BuildSynchronizedFields::class);
        $this->field_collection_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter
        );
        $this->semantic_checker         = Mockery::mock(CheckSemantic::class);
        $this->required_field_checker   = Mockery::mock(CheckRequiredField::class);
        $this->workflow_checker         = Mockery::mock(CheckWorkflow::class);

        $this->checker = new ProgramIncrementArtifactCreatorChecker(
            $projects_collection_builder,
            $trackers_builder,
            $this->field_collection_builder,
            $this->semantic_checker,
            $this->required_field_checker,
            $this->workflow_checker,
            new NullLogger()
        );

        $this->project = new Project(
            ['group_id' => '101', 'unix_group_name' => 'proj01', 'group_name' => 'Project 01']
        );

        $this->project_data = ProjectAdapter::build($this->project);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $program = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnTrue();
        $this->workflow_checker->shouldReceive('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->andReturnTrue();

        self::assertTrue($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsTrueWhenAProjectHasNoTeamProjects(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->program_store->shouldReceive('getTeamProjectIdsForGivenProgramProject')->andReturn([]);

        self::assertTrue($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsFalseIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $planning = new \Planning(1, 'Incorrect', $this->project->getID(), '', '');
        $planning->setPlanningTracker(new \NullTracker());
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);

        $first_team_project = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );
        $this->program_store->shouldReceive('getTeamProjectIdsForGivenProgramProject')
            ->andReturn([['team_project_id' => $first_team_project->getID()]]);
        $this->project_manager->shouldReceive('getProject')
            ->with($first_team_project->getID())
            ->once()
            ->andReturn($first_team_project);

        self::assertFalse($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnFalse();

        self::assertFalse($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->mockTeamMilestoneTrackers($this->project, false);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        self::assertFalse($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $this->fields_adapter->shouldReceive('build')
            ->andThrow(new FieldRetrievalException(1, 'title'));

        self::assertFalse($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $this->buildSynchronizedFields(false);

        self::assertFalse($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsFalseIfTrackersHaveRequiredFieldsThatCannotBeSynchronized(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnFalse();

        self::assertFalse($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    public function testItReturnsFalseIfTeamTrackersAreUsingSynchronizedFieldsInWorkflowRules(): void
    {
        $user                      = UserTestBuilder::aUser()->build();
        $tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $program                   = new \Tuleap\ProgramManagement\Project(101, 'my_project', "My project");

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnTrue();
        $this->workflow_checker->shouldReceive('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->andReturnFalse();

        self::assertFalse($this->checker->canProgramIncrementBeCreated($program_increment_tracker, $program, $user));
    }

    private function mockTeamMilestoneTrackers(Project $project, bool $user_can_submit_artifact = true): void
    {
        $first_team_project  = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );
        $second_team_project = new \Project(
            ['group_id' => '198', 'unix_group_name' => 'proj03', 'group_name' => 'Project 03']
        );

        $this->program_store->shouldReceive('getTeamProjectIdsForGivenProgramProject')
            ->andReturn([['team_project_id' => $project->getID()]]);
        $this->project_manager->shouldReceive('getProject')
            ->with($project->getID())
            ->once()
            ->andReturn($project);

        $first_milestone_tracker = Mockery::mock(\Tracker::class);
        $first_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $first_milestone_tracker->shouldReceive('getGroupId')->andReturn($first_team_project->getID());
        $first_milestone_tracker->shouldReceive('getId')->andReturn(1);
        $first_milestone_tracker->shouldReceive('getProject')->andReturn($first_team_project);
        $second_milestone_tracker = Mockery::mock(\Tracker::class);
        $second_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $second_milestone_tracker->shouldReceive('getGroupId')->andReturn($second_team_project->getID());
        $second_milestone_tracker->shouldReceive('getId')->andReturn(2);
        $second_milestone_tracker->shouldReceive('getProject')->andReturn($second_team_project);
        $planning = new \Planning(1, 'Release', $this->project->getID(), '', '');
        $planning->setBacklogTrackers([$first_milestone_tracker, $second_milestone_tracker]);
        $planning->setPlanningTracker($first_milestone_tracker);

        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);
    }

    private function buildSynchronizedFields(bool $submitable): void
    {
        $title_field = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->mockField($title_field, 1, true, true);
        $title_field_data = new Field($title_field);

        $artifact_link = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->mockField($artifact_link, 1, $submitable, true);
        $artifact_link_field_data = new Field($artifact_link);

        $description_field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->mockField($description_field, 2, true, true);
        $description_field_data = new Field($description_field);

        $status_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockField($status_field, 3, true, true);
        $status_field_data = new Field($status_field);

        $field_start_date = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_start_date, 4, true, true);
        $start_date_field_data = new Field($field_start_date);

        $field_end_date = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_end_date, 5, true, true);
        $end_date_field_data = new Field($field_end_date);

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
        $this->fields_adapter->shouldReceive('build')->andReturn($synchronized_fields);
    }

    private function mockField(MockInterface $field, int $id, bool $submitable, bool $updatable): void
    {
        $field->shouldReceive('getId')->andReturn((string) $id);
        $field->shouldReceive('userCanSubmit')->andReturn($submitable);
        $field->shouldReceive('userCanUpdate')->andReturn($updatable);
    }
}
