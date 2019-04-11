<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TrackerFactory;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\GlobalLanguageMock;

class BaselineArtifactRepositoryAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var  BaselineArtifactRepositoryAdapter */
    private $adapter;

    /** @var Tracker_ArtifactFactory|MockInterface */
    private $artifact_factory;

    /** @var AdapterPermissions|MockInterface */
    private $adapter_permissions;

    /** @var Tracker_Artifact_ChangesetFactory|MockInterface */
    private $changeset_factory;

    /** @var SemanticValueAdapter|MockInterface */
    private $semantic_value_adapter;

    /** @var ArtifactLinkRepository|MockInterface */
    private $artifact_link_adapter;

    /**
     * @var PFUser
     */
    private $current_user;

    /** @before */
    public function createInstance()
    {
        $this->artifact_factory       = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->adapter_permissions    = Mockery::mock(AdapterPermissions::class);
        $this->changeset_factory      = Mockery::mock(Tracker_Artifact_ChangesetFactory::class);
        $this->semantic_value_adapter = Mockery::mock(SemanticValueAdapter::class);
        $this->artifact_link_adapter  = Mockery::mock(ArtifactLinkRepository::class);

        $this->adapter = new BaselineArtifactRepositoryAdapter(
            $this->artifact_factory,
            $this->adapter_permissions,
            $this->changeset_factory,
            $this->semantic_value_adapter,
            $this->artifact_link_adapter
        );
    }

    /** @before */
    public function createCurrentUser()
    {
        $this->current_user = new PFUser();
    }

    public function testFindById()
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker->getProject')
            ->andReturn(ProjectFactory::one());

        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with(1)
            ->andReturn($artifact);

        $this->adapter_permissions
            ->shouldReceive('canUserReadArtifact')
            ->with($this->current_user, $artifact)
            ->andReturn(true);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getArtifact->getTracker')
            ->andReturn(
                TrackerFactory::one()
                    ->id(10)
                    ->itemName('Tracker name')
                    ->build()
            );
        $this->changeset_factory
            ->shouldReceive('getLastChangeset')
            ->with($artifact)
            ->andReturn($changeset);

        $this->semantic_value_adapter
            ->shouldReceive(
                [
                    'findTitle'         => 'Custom title',
                    'findDescription'   => 'Custom description',
                    'findStatus'        => 'Custom status',
                    'findInitialEffort' => 5
                ]
            )
            ->with($changeset, $this->current_user);

        $this->artifact_link_adapter
            ->shouldReceive('findLinkedArtifactIds')
            ->with($this->current_user, $changeset)
            ->andReturn([2, 3, 4]);

        $baseline_artifact = $this->adapter->findById($this->current_user, 1);

        $this->assertEquals('Custom title', $baseline_artifact->getTitle());
        $this->assertEquals('Custom description', $baseline_artifact->getDescription());
        $this->assertEquals('Custom status', $baseline_artifact->getStatus());
        $this->assertEquals(5, $baseline_artifact->getInitialEffort());
        $this->assertEquals([2, 3, 4], $baseline_artifact->getLinkedArtifactIds());
    }

    public function testFindByIdAt()
    {
        $date     = DateTimeFactory::one();
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker->getProject')
            ->andReturn(ProjectFactory::one());

        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with(1)
            ->andReturn($artifact);

        $this->adapter_permissions
            ->shouldReceive('canUserReadArtifact')
            ->with($this->current_user, $artifact)
            ->andReturn(true);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getArtifact->getTracker')
            ->andReturn(
                TrackerFactory::one()
                    ->id(10)
                    ->itemName('Tracker name')
                    ->build()
            );
        $this->changeset_factory
            ->shouldReceive('getChangesetAtTimestamp')
            ->with($artifact, $date->getTimestamp())
            ->andReturn($changeset);

        $this->semantic_value_adapter
            ->shouldReceive(
                [
                    'findTitle'         => 'Custom title',
                    'findDescription'   => 'Custom description',
                    'findStatus'        => 'Custom status',
                    'findInitialEffort' => 5
                ]
            )
            ->with($changeset, $this->current_user);

        $this->artifact_link_adapter
            ->shouldReceive('findLinkedArtifactIds')
            ->with($this->current_user, $changeset)
            ->andReturn([2, 3, 4]);

        $baseline_artifact = $this->adapter->findByIdAt($this->current_user, 1, $date);

        $this->assertEquals('Custom title', $baseline_artifact->getTitle());
        $this->assertEquals('Custom description', $baseline_artifact->getDescription());
        $this->assertEquals('Custom status', $baseline_artifact->getStatus());
        $this->assertEquals(5, $baseline_artifact->getInitialEffort());
        $this->assertEquals([2, 3, 4], $baseline_artifact->getLinkedArtifactIds());
    }
}
