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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\ArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveTrackerOfArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;

final class TrackerOfArtifactRetriever implements RetrieveTrackerOfArtifact
{
    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private \TrackerFactory $tracker_factory
    ) {
    }

    public function getTrackerOfArtifact(ArtifactIdentifier $artifact): TrackerIdentifier
    {
        $full_artifact = $this->artifact_factory->getArtifactById($artifact->getId());
        if (! $full_artifact) {
            throw new ArtifactNotFoundException($artifact);
        }
        $tracker_id = $full_artifact->getTrackerId();
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new TrackerNotFoundException($tracker_id);
        }
        return TrackerReferenceProxy::fromTracker($tracker);
    }
}
