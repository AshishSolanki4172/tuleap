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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\ChangesetRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DateValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\FieldValuesGathererRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsGatherer;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Adapter\Workspace\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildProgramIncrementUpdateProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementUpdateProcessor;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\ChangesetFromXmlDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

final class ProgramIncrementUpdateProcessorBuilder implements BuildProgramIncrementUpdateProcessor
{
    public function getProcessor(): ProcessProgramIncrementUpdate
    {
        $logger                   = \BackendLogger::getDefaultLogger('program_management_syslog');
        $artifact_factory         = \Tracker_ArtifactFactory::instance();
        $tracker_factory          = \TrackerFactory::instance();
        $form_element_factory     = \Tracker_FormElementFactory::instance();
        $user_retriever           = new UserManagerAdapter(\UserManager::instance());
        $artifact_links_usage_dao = new ArtifactLinksUsageDao();
        $artifact_changeset_dao   = new \Tracker_Artifact_ChangesetDao();
        $visibility_verifier      = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        $changeset_creator = new \Tracker_Artifact_Changeset_NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $form_element_factory,
                new ArtifactLinkValidator(
                    $artifact_factory,
                    new NaturePresenterFactory(
                        new NatureDao(),
                        $artifact_links_usage_dao
                    ),
                    $artifact_links_usage_dao
                ),
                new WorkflowUpdateChecker(
                    new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(
                                \TransitionFactory::instance(),
                                new SimpleWorkflowDao()
                            ),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance()
                    ),
                ),
            ),
            new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory),
            $artifact_changeset_dao,
            new \Tracker_Artifact_Changeset_CommentDao(),
            $artifact_factory,
            \EventManager::instance(),
            \ReferenceManager::instance(),
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
            $transaction_executor,
            new ArtifactChangesetSaver(
                $artifact_changeset_dao,
                $transaction_executor,
                new \Tracker_ArtifactDao(),
                new ChangesetFromXmlDao()
            ),
            new ParentLinkAction($artifact_factory),
            new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao())
        );

        $synchronized_fields_gatherer = new SynchronizedFieldsGatherer(
            $tracker_factory,
            new \Tracker_Semantic_TitleFactory(),
            new \Tracker_Semantic_DescriptionFactory(),
            new \Tracker_Semantic_StatusFactory(),
            new SemanticTimeframeBuilder(
                new SemanticTimeframeDao(),
                $form_element_factory,
                $tracker_factory,
                new LinksRetriever(
                    new ArtifactLinkFieldValueDao(),
                    $artifact_factory
                )
            ),
            $form_element_factory
        );
        return new ProgramIncrementUpdateProcessor(
            MessageLog::buildFromLogger($logger),
            $synchronized_fields_gatherer,
            new FieldValuesGathererRetriever($artifact_factory, $form_element_factory),
            new ChangesetRetriever($artifact_factory),
            new MirroredTimeboxesDao(),
            $visibility_verifier,
            new TrackerOfArtifactRetriever($artifact_factory, $tracker_factory),
            new StatusValueMapper($form_element_factory),
            new ChangesetAdder(
                $artifact_factory,
                $user_retriever,
                new ChangesetValuesFormatter(
                    new ArtifactLinkValueFormatter(),
                    new DescriptionValueFormatter(),
                    new DateValueFormatter()
                ),
                $changeset_creator
            )
        );
    }
}
