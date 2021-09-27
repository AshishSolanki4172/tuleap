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

namespace Tuleap\ProgramManagement\REST\v1\Helper;

use Psr\Log\NullLogger;
use REST_TestDataBuilder;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Events\ArtifactCreatedProxy;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingProgramIncrementCreationDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationDispatcher;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\TaskBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\ProgramManagementProjectAdapter;
use Tuleap\ProgramManagement\Adapter\Team\TeamAdapter;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Team\Creation\Team;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCollection;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Queue\QueueFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use UserManager;

final class ProgramDataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_TEAM_NAME    = 'team';
    public const PROJECT_PROGRAM_NAME = 'program';

    public ReplicationDataAdapter $replication_data_adapter;
    private ProgramIncrementCreationDispatcher $creation_dispatcher;
    private ?\PFUser $user;
    private \Tracker $program_increment;
    private \Tracker $user_story;
    private \Tracker $feature;
    private \Tracker_ArtifactFactory $artifact_factory;
    private PendingProgramIncrementCreationDAO $pending_program_increments_dao;

    public function setUp(): void
    {
        echo 'Setup Program Management REST Tests configuration' . PHP_EOL;

        $user_manager                         = UserManager::instance();
        $user_adapter                         = new UserManagerAdapter($user_manager);
        $this->artifact_factory               = Tracker_ArtifactFactory::instance();
        $changeset_factory                    = Tracker_Artifact_ChangesetFactoryBuilder::build();
        $team_dao                             = new TeamDao();
        $program_dao                          = new ProgramDao();
        $project_permissions_verifier         = new ProjectPermissionVerifier(RetrieveUserStub::withGenericUser());
        $this->pending_program_increments_dao = new PendingProgramIncrementCreationDAO();

        $team_builder = new TeamAdapter($this->project_manager, $program_dao, new ExplicitBacklogDao(), $user_adapter);

        $this->replication_data_adapter = new ReplicationDataAdapter(
            $this->artifact_factory,
            $user_manager,
            $this->pending_program_increments_dao,
            $changeset_factory,
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            $program_dao,
            new ProgramManagementProjectAdapter($this->project_manager),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            new ArtifactVisibleVerifier($this->artifact_factory, $user_adapter)
        );

        $null_logger               = new NullLogger();
        $this->creation_dispatcher = new ProgramIncrementCreationDispatcher(
            $null_logger,
            new QueueFactory($null_logger),
            $this->replication_data_adapter,
            new TaskBuilder()
        );

        $this->user = $user_manager->getUserByUserName(\TestDataBuilder::TEST_USER_1_NAME);

        $program_project = $this->getProjectByShortName(self::PROJECT_PROGRAM_NAME);
        $team_project    = $this->getProjectByShortName(self::PROJECT_TEAM_NAME);


        $program = ProgramForAdministrationIdentifier::fromProject(
            $team_dao,
            $project_permissions_verifier,
            UserProxy::buildFromPFUser($this->user),
            ProjectProxy::buildFromProject($program_project)
        );

        $team = Team::buildForRestTest($team_builder, (int) $team_project->getID());
        $team_dao->save(TeamCollection::fromProgramAndTeams($program, $team));

        $tracker_factory = \TrackerFactory::instance();
        assert($tracker_factory instanceof \TrackerFactory);
        $program_trackers = $tracker_factory->getTrackersByGroupId((int) $program_project->getID());
        $team_trackers    = $tracker_factory->getTrackersByGroupId((int) $team_project->getID());

        $this->feature    = $this->getTrackerByName($program_trackers, "features");
        $this->user_story = $this->getTrackerByName($team_trackers, "story");

        $this->program_increment = $this->getTrackerByName($program_trackers, "pi");

        $this->linkFeatureAndUserStories();
        $this->linkProgramIncrementToMirroredRelease();
    }

    private function linkFeatureAndUserStories(): void
    {
        $feature_list    = $this->artifact_factory->getArtifactsByTrackerId($this->feature->getId());
        $user_story_list = $this->artifact_factory->getArtifactsByTrackerId($this->user_story->getId());

        $featureA = $this->getArtifactByTitle($feature_list, "FeatureA");
        $featureB = $this->getArtifactByTitle($feature_list, "FeatureB");
        $us1      = $this->getArtifactByTitle($user_story_list, "US1");
        $us2      = $this->getArtifactByTitle($user_story_list, "US2");

        $featureA_artifact_link = $featureA->getAnArtifactLinkField($this->user);
        assert($featureA_artifact_link instanceof \Tracker_FormElement_Field_ArtifactLink);
        $fieldsA_data                                                     = [];
        $fieldsA_data[$featureA_artifact_link->getId()]['new_values']     = (string) $us1->getId();
        $fieldsA_data[$featureA_artifact_link->getId(
        )]['nature']                                                      = Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD;
        $fieldsA_data[$featureA_artifact_link->getId()]['natures']        = [
            $us1->getId() => Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
        ];
        $fieldsA_data[$featureA_artifact_link->getId()]['removed_values'] = [];

        $featureA->createNewChangeset($fieldsA_data, "", $this->user);

        $featureB_artifact_link = $featureB->getAnArtifactLinkField($this->user);
        assert($featureB_artifact_link instanceof \Tracker_FormElement_Field_ArtifactLink);
        $fieldsB_data                                                     = [];
        $fieldsB_data[$featureB_artifact_link->getId()]['new_values']     = (string) $us2->getId();
        $fieldsB_data[$featureB_artifact_link->getId()]['removed_values'] = [];
        $featureA->createNewChangeset($fieldsB_data, "", $this->user);
    }

    public function linkProgramIncrementToMirroredRelease(): void
    {
        $program_increment_list = $this->artifact_factory->getArtifactsByTrackerId($this->program_increment->getId());

        $pi = $this->getArtifactByTitle($program_increment_list, "PI");

        $tracker_event              = new ArtifactCreated($pi, $pi->getLastChangeset(), $this->user);
        $created_event              = ArtifactCreatedProxy::fromArtifactCreated($tracker_event);
        $program_increment_creation = ProgramIncrementCreation::fromArtifactCreatedEvent(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            $created_event
        );
        if (! $program_increment_creation) {
            return;
        }
        $this->pending_program_increments_dao->storeCreation($program_increment_creation);
        $replication_data = $this->replication_data_adapter->buildFromArtifactAndUserId(
            $pi->getId(),
            (int) $pi->getSubmittedBy()
        );

        if ($replication_data === null) {
            return;
        }

        $this->creation_dispatcher->processProgramIncrementCreation($replication_data);
    }

    /**
     * @param \Tracker[] $trackers
     */
    private function getTrackerByName(array $trackers, string $name): \Tracker
    {
        foreach ($trackers as $tracker) {
            if ($tracker->getItemName() === $name) {
                return $tracker;
            }
        }

        throw new \LogicException("Can not find asked tracker $name");
    }

    /**
     * @param Artifact[] $artifacts
     */
    private function getArtifactByTitle(array $artifacts, string $title): Artifact
    {
        foreach ($artifacts as $artifact) {
            if ($artifact->getTitle() === $title) {
                return $artifact;
            }
        }

        throw new \LogicException("Can not find asked artifact with title $title");
    }

    private function getProjectByShortName(string $short_name): \Project
    {
        $program_project = $this->project_manager->getProjectByUnixName($short_name);
        if (! $program_project) {
            throw new \LogicException(sprintf('Could not find project with short name %s', $short_name));
        }

        return $program_project;
    }
}
