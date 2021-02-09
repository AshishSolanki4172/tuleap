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

namespace Tuleap\ScaledAgile\Adapter\Program\Feature;

use PFUser;
use Tuleap\ScaledAgile\REST\v1\FeatureRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class FeatureRepresentationBuilder
{
    /**
     * @var BackgroundColorRetriever
     */
    private $retrieve_background_color;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        \Tracker_FormElementFactory $form_element_factory,
        BackgroundColorRetriever $retrieve_background_color
    ) {
        $this->artifact_factory          = $artifact_factory;
        $this->form_element_factory      = $form_element_factory;
        $this->retrieve_background_color = $retrieve_background_color;
    }

    public function buildFeatureRepresentation(
        PFUser $user,
        int $artifact_id,
        int $title_field_id,
        string $artifact_title
    ): ?FeatureRepresentation {
        $full_artifact = $this->artifact_factory->getArtifactById($artifact_id);

        if (! $full_artifact || ! $full_artifact->userCanView($user)) {
            return null;
        }

        $title = $this->form_element_factory->getFieldById($title_field_id);
        if (! $title || ! $title->userCanRead($user)) {
            return null;
        }

        return new FeatureRepresentation(
            $artifact_id,
            $artifact_title,
            $full_artifact->getXRef(),
            MinimalTrackerRepresentation::build($full_artifact->getTracker()),
            $this->retrieve_background_color->retrieveBackgroundColor($full_artifact, $user)
        );
    }
}
