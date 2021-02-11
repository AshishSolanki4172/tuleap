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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\Program\Backlog\Feature\FeaturesStore;
use Tuleap\ProgramManagement\Program\Backlog\Feature\RetrieveFeatures;
use Tuleap\ProgramManagement\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;

final class FeatureElementsRetriever implements RetrieveFeatures
{
    /**
     * @var FeaturesStore
     */
    private $features_store;
    /**
     * @var BuildProgram
     */
    private $build_program;

    /**
     * @var FeatureRepresentationBuilder
     */
    private $feature_representation_builder;

    public function __construct(
        BuildProgram $build_program,
        FeaturesStore $features_store,
        FeatureRepresentationBuilder $feature_representation_builder
    ) {
        $this->features_store                 = $features_store;
        $this->build_program                  = $build_program;
        $this->feature_representation_builder = $feature_representation_builder;
    }

    /**
     * @return FeatureRepresentation[]
     *
     * @throws \Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException
     * @throws \Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException
     */
    public function retrieveFeaturesToBePlanned(int $program_id, \PFUser $user): array
    {
        $program = $this->build_program->buildExistingProgramProject($program_id, $user);

        $to_be_planned_artifacts = $this->features_store->searchPlannableFeatures($program);

        $elements = [];
        foreach ($to_be_planned_artifacts as $artifact) {
            $feature = $this->feature_representation_builder->buildFeatureRepresentation(
                $user,
                $artifact['artifact_id'],
                $artifact['field_title_id'],
                $artifact['artifact_title']
            );
            if ($feature) {
                $elements[] = $feature;
            }
        }

        return $elements;
    }
}
