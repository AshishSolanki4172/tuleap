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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\VerifyIsLinkedToAnotherMilestone;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchPlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class UserStoryLinkedToFeatureVerifier implements VerifyLinkedUserStoryIsNotPlanned
{
    public function __construct(
        private SearchPlannedUserStory $search_planned_user_story,
        private BuildPlanning $planning_adapter,
        private Tracker_ArtifactFactory $artifact_factory,
        private RetrieveUser $retrieve_user,
        private SearchChildrenOfFeature $search_children_of_feature,
        private VerifyIsLinkedToAnotherMilestone $verify_is_linked_to_program_increment,
    ) {
    }

    public function isLinkedToAtLeastOnePlannedUserStory(
        UserIdentifier $user_identifier,
        FeatureIdentifier $feature,
    ): bool {
        $planned_user_stories = $this->search_planned_user_story->getPlannedUserStory($feature->id);
        foreach ($planned_user_stories as $user_story) {
            try {
                $planning = $this->planning_adapter->getRootPlanning($user_identifier, $user_story['project_id']);
            } catch (TopPlanningNotFoundInProjectException $e) {
                continue;
            }

            $is_linked_to_a_sprint_in_mirrored_program_increments = $this->verify_is_linked_to_program_increment->isLinkedToASprintInMirroredProgramIncrement(
                $user_story['user_story_id'],
                $planning->getPlanningTracker()->getId(),
                $user_story['project_id']
            );
            if ($is_linked_to_a_sprint_in_mirrored_program_increments) {
                return true;
            }
        }

        return false;
    }

    public function hasStoryLinked(UserIdentifier $user_identifier, FeatureIdentifier $feature): bool
    {
        $user            = $this->retrieve_user->getUserWithId($user_identifier);
        $linked_children = $this->search_children_of_feature->getChildrenOfFeatureInTeamProjects($feature->id);
        foreach ($linked_children as $linked_child) {
            $child = $this->artifact_factory->getArtifactByIdUserCanView($user, $linked_child['children_id']);
            if ($child) {
                return true;
            }
        }

        return false;
    }
}
