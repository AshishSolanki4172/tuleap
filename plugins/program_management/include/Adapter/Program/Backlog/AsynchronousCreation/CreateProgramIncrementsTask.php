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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeaturePlanner;
use Tuleap\ProgramManagement\Program\Backlog\AsynchronousCreation\CreateTaskProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationException;
use Tuleap\ProgramManagement\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildFieldValues;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Team\ProgramIncrementTrackerRetrievalException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Program\Backlog\TrackerCollectionFactory;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;

final class CreateProgramIncrementsTask implements CreateTaskProgramIncrement
{
    /**
     * @var BuildFieldValues
     */
    private $changeset_collection_adapter;
    /**
     * @var TeamProjectsCollectionBuilder
     */
    private $projects_collection_builder;
    /**
     * @var TrackerCollectionFactory
     */
    private $scale_tracker_factory;
    /**
     * @var ProgramIncrementsCreator
     */
    private $program_increment_creator;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;
    /**
     * @var FeaturePlanner
     */
    private $feature_planner;

    public function __construct(
        BuildFieldValues $changeset_collection_adapter,
        TeamProjectsCollectionBuilder $projects_collection_builder,
        TrackerCollectionFactory $scale_tracker_factory,
        ProgramIncrementsCreator $program_increment_creator,
        LoggerInterface $logger,
        PendingArtifactCreationStore $pending_artifact_creation_store,
        FeaturePlanner $feature_planner
    ) {
        $this->changeset_collection_adapter    = $changeset_collection_adapter;
        $this->projects_collection_builder     = $projects_collection_builder;
        $this->scale_tracker_factory           = $scale_tracker_factory;
        $this->program_increment_creator       = $program_increment_creator;
        $this->logger                          = $logger;
        $this->pending_artifact_creation_store = $pending_artifact_creation_store;
        $this->feature_planner                 = $feature_planner;
    }

    public function createProgramIncrements(ReplicationData $replication_data): void
    {
        try {
            $this->create($replication_data);
        } catch (ProgramIncrementTrackerRetrievalException | ProgramIncrementCreationException | FieldRetrievalException | FieldSynchronizationException $exception) {
            $this->logger->error('Error during creation of project increments ', ['exception' => $exception]);
        }
    }

    /**
     * @throws ProgramIncrementCreationException
     * @throws ProgramIncrementTrackerRetrievalException
     * @throws FieldRetrievalException
     * @throws FieldSynchronizationException
     */
    private function create(ReplicationData $replication_data): void
    {
        $copied_values = $this->changeset_collection_adapter->buildCollection($replication_data);

        $team_projects = $this->projects_collection_builder->getTeamProjectForAGivenProgramProject($replication_data->getProject());

        $team_program_increments_tracker = $this->scale_tracker_factory->buildFromTeamProjects(
            $team_projects,
            $replication_data->getUser()
        );

        $this->program_increment_creator->createProgramIncrements(
            $copied_values,
            $team_program_increments_tracker,
            $replication_data->getUser()
        );

        $this->pending_artifact_creation_store->deleteArtifactFromPendingCreation(
            (int) $replication_data->getArtifact()->getId(),
            (int) $replication_data->getUser()->getId()
        );

        $artifact_updated = new ArtifactUpdated(
            $replication_data->getFullChangeset()->getArtifact(),
            $replication_data->getUser()
        );
        $this->feature_planner->plan($artifact_updated);
    }
}
