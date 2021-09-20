<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\IterationCreatorChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ProgramIncrementCreatorChecker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\ProjectReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class TrackerErrorPresenterTest extends TestCase
{
    private TrackerReference $tracker;
    private UserIdentifierStub $user_identifier;
    private ConfigurationErrorsGatherer $gatherer;

    protected function setUp(): void
    {
        $program_increment_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
        $iteration_checker         = $this->createStub(IterationCreatorChecker::class);
        $build_program             = BuildProgramStub::stubValidProgram();
        $teams_searcher            = SearchTeamsOfProgramStub::buildTeams(1);
        $project_builder           = new BuildProjectStub();
        $retrieve_user             = RetrieveUserStub::withUser(UserTestBuilder::aUser()->build());
        $this->tracker             = TrackerReferenceStub::withDefaults();
        $this->user_identifier     = UserIdentifierStub::buildGenericUser();

        $program_increment_checker->expects(self::once())->method('canCreateAProgramIncrement');
        $iteration_checker->expects(self::once())->method('canCreateAnIteration');

        $this->gatherer =  new ConfigurationErrorsGatherer(
            $build_program,
            $program_increment_checker,
            $iteration_checker,
            $teams_searcher,
            $project_builder,
            $retrieve_user,
        );
    }

    public function testItHasSemanticErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addSemanticError(
            'Title',
            'title',
            [TrackerReferenceStub::withId(1), TrackerReferenceStub::withId(2), TrackerReferenceStub::withId(3)]
        );

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->semantic_errors);
    }

    public function testItHasRequiredErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addRequiredFieldError(
            $this->tracker,
            ProjectReferenceBuilder::buildGeneric(),
            100,
            'My field'
        );

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->required_field_errors);
    }

    public function testItHasWorkflowErrorsForTransition(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addWorkflowTransitionRulesError(
            $this->tracker,
            ProjectReferenceBuilder::buildGeneric()
        );


        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->transition_rule_error);
    }

    public function testItHasWorkflowErrorsForGlobalRules(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addWorkflowTransitionDateRulesError(
            $this->tracker,
            ProjectReferenceBuilder::buildGeneric()
        );

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->transition_rule_date_error);
    }

    public function testItHasWorkflowErrorsForFieldDependency(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addWorkflowDependencyError(
            $this->tracker,
            ProjectReferenceBuilder::buildGeneric()
        );

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->field_dependency_error);
    }

    public function testItHasPermissionErrorsWhenNotSubmittable(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addSubmitFieldPermissionError(
            100,
            "My custom field",
            $this->tracker,
            ProjectReferenceBuilder::buildGeneric()
        );

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->non_submittable_field_errors);
    }

    public function testItHasPermissionErrorsWhenNotEditable(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addUpdateFieldPermissionError(
            100,
            "My custom field",
            $this->tracker,
            ProjectReferenceBuilder::buildGeneric()
        );

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->non_updatable_field_errors);
    }

    public function testItHasErrorWhenUserCanNotSubmitInTeam(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->userCanNotSubmitInTeam($this->tracker);

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->team_tracker_id_errors);
    }

    public function testItHasErrorSemanticStatusErrorWhenStatusMissingInTeam(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addMissingSemanticInTeamErrors(
            [TrackerReferenceStub::withId(1), TrackerReferenceStub::withId(2), TrackerReferenceStub::withId(3)]
        );

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->status_missing_in_teams);
    }

    public function testItHasErrorSemanticStatusErrorWhenStatusFieldNotFound(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addSemanticNoStatusFieldError(1);

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->semantic_status_no_field);
    }

    public function testItHasErrorSemanticStatusErrorWhenStatusMissingValues(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addMissingValueInSemantic(['On going'], [TrackerReferenceStub::withId(1)]);

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($presenter->has_presenter_errors);
        self::assertNotNull($presenter);
        self::assertNotEmpty($presenter->semantic_status_missing_values);
    }

    public function testItDoesNotHaveAnyError(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);

        $presenter = TrackerErrorPresenter::fromTracker(
            $this->gatherer,
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertNull($presenter);
    }
}
