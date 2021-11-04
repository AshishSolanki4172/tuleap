<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\AgileDashboard\REST\v1\Milestone\OriginalProjectCollector;
use Tuleap\CLI\Events\GetWhitelistedKeys;
use Tuleap\Dashboard\Project\DisplayCreatedProjectModalPresenter;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Event\Events\HasCurrentProjectParentProjects;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Glyph\GlyphLocation;
use Tuleap\Glyph\GlyphLocationsCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Events\ArtifactCreatedProxy;
use Tuleap\ProgramManagement\Adapter\Events\ArtifactUpdatedProxy;
use Tuleap\ProgramManagement\Adapter\Events\CanSubmitNewArtifactEventProxy;
use Tuleap\ProgramManagement\Adapter\Events\CollectLinkedProjectsProxy;
use Tuleap\ProgramManagement\Adapter\Events\ProgramIncrementCreationEventProxy;
use Tuleap\ProgramManagement\Adapter\Events\ProgramIncrementUpdateEventProxy;
use Tuleap\ProgramManagement\Adapter\Events\ProjectServiceBeforeActivationProxy;
use Tuleap\ProgramManagement\Adapter\Events\RootPlanningEditionEventProxy;
use Tuleap\ProgramManagement\Adapter\Events\ServiceDisabledCollectorProxy;
use Tuleap\ProgramManagement\Adapter\FeatureFlag\ForgeConfigAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Admin\CanPrioritizeItems\ProjectUGroupCanPrioritizeItemsPresentersBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\CanPrioritizeItems\UGroupRepresentationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration\ConfigurationErrorPresenterBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\PlannableTrackersConfiguration\PotentialPlannableTrackersConfigurationPresentersBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ChangesetDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\IterationCreationProcessorBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\LastChangesetRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationDispatcher;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationProcessorBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ProgramIncrementUpdateDispatcher;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ProgramIncrementUpdateProcessorBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\RequiredFieldVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\SemanticsVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\StatusIsAlignedVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\WorkflowVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationsLinkedToProgramIncrementDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProjectFromTrackerRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\FieldPermissionsVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsGatherer;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\TrackerFromFieldRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\SourceArtifactNatureAnalyzer;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Rank\FeaturesRankOrderer;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TimeboxArtifactLinkPresenter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ArtifactsExplicitTopBacklogDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ArtifactTopBacklogActionBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\MassChangeTopBacklogActionBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\MassChangeTopBacklogActionProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\MassChangeTopBacklogSourceInformation;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\PlannedFeatureDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ProcessTopBacklogChange;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow\AddToTopBacklogPostAction;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow\AddToTopBacklogPostActionDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow\AddToTopBacklogPostActionFactory;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow\AddToTopBacklogPostActionJSONParser;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow\AddToTopBacklogPostActionRepresentation;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow\AddToTopBacklogPostActionValueUpdater;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeaturesDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsVisibleFeatureAdapter;
use Tuleap\ProgramManagement\Adapter\Program\IterationTracker\VisibleIterationTrackerRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\Program\ProgramIncrementTracker\VisibleProgramIncrementTrackerRetriever;
use Tuleap\ProgramManagement\Adapter\Program\ProgramUserGroupRetriever;
use Tuleap\ProgramManagement\Adapter\ProjectReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\ProjectAdmin\PermissionPerGroupSectionBuilder;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Team\PossibleParentSelectorProxy;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactIdentifierProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramsSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\ScrumBlocksServiceVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\TeamsSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerSemantics;
use Tuleap\ProgramManagement\Adapter\Workspace\UGroupManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserCanSubmitInTrackerVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\WorkspaceDAO;
use Tuleap\ProgramManagement\Adapter\XML\ProgramManagementConfigXMLImporter;
use Tuleap\ProgramManagement\Adapter\XML\ProgramManagementXMLConfigExtractor;
use Tuleap\ProgramManagement\Adapter\XML\ProgramManagementXMLConfigParser;
use Tuleap\ProgramManagement\DisplayAdminProgramManagementController;
use Tuleap\ProgramManagement\DisplayPlanIterationsController;
use Tuleap\ProgramManagement\DisplayProgramBacklogController;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ArtifactCreatedHandler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ArtifactUpdatedHandler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreationDetector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationEventHandler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementUpdateEventHandler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CanSubmitNewArtifactHandler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\IterationCreatorChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ProgramIncrementCreatorChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\TimeboxCreatorChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\NatureAnalyzerException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogActionArtifactSourceInformation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogActionMassChangeSourceInformation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanCreator;
use Tuleap\ProgramManagement\Domain\Service\ProjectServiceBeforeActivationHandler;
use Tuleap\ProgramManagement\Domain\Service\ServiceDisabledCollectorHandler;
use Tuleap\ProgramManagement\Domain\Team\PossibleParentHandler;
use Tuleap\ProgramManagement\Domain\Team\RootPlanning\RootPlanningEditionHandler;
use Tuleap\ProgramManagement\Domain\Workspace\CollectLinkedProjectsHandler;
use Tuleap\ProgramManagement\Domain\Workspace\ComponentInvolvedVerifier;
use Tuleap\ProgramManagement\EventRedirectAfterArtifactCreationOrUpdateHandler;
use Tuleap\ProgramManagement\ProgramManagementBreadCrumbsBuilder;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\RedirectParameterInjector;
use Tuleap\ProgramManagement\REST\ResourcesInjector;
use Tuleap\ProgramManagement\Templates\ProgramTemplate;
use Tuleap\ProgramManagement\Templates\TeamTemplate;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\Registration\Template\Events\CollectCategorisedExternalTemplatesEvent;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\Masschange\TrackerMasschangeGetExternalActionsEvent;
use Tuleap\Tracker\Masschange\TrackerMasschangeProcessExternalActionsEvent;
use Tuleap\Tracker\REST\v1\Event\GetExternalPostActionJsonParserEvent;
use Tuleap\Tracker\REST\v1\Event\PostActionVisitExternalActionsEvent;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\CheckPostActionsForTracker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Workflow\Event\GetWorkflowExternalPostActionsValueUpdater;
use Tuleap\Tracker\Workflow\Event\TransitionDeletionEvent;
use Tuleap\Tracker\Workflow\Event\WorkflowDeletionEvent;
use Tuleap\Tracker\Workflow\PostAction\ExternalPostActionSaveObjectEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalPostActionPluginsEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoriesEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoryByNameEvent;
use Tuleap\Tracker\Workflow\PostAction\GetPostActionShortNameFromXmlTagNameEvent;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../../cardwall/include/cardwallPlugin.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class program_managementPlugin extends Plugin
{
    public const SERVICE_SHORTNAME = 'plugin_program_management';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-program_management', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(RootPlanningEditionEvent::NAME);
        $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES, 'getArtifactLinkNatures');
        $this->addHook(NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER, 'getNaturePresenter');
        $this->addHook(
            Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES,
            'trackerAddSystemNatures'
        );
        $this->addHook(CanSubmitNewArtifact::NAME);
        $this->addHook(ArtifactCreated::NAME);
        $this->addHook(ArtifactUpdated::NAME);
        $this->addHook(ArtifactDeleted::NAME);
        $this->addHook(WorkerEvent::NAME);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(PlanningAdministrationDelegation::NAME);
        $this->addHook('tracker_usage', 'trackerUsage');
        $this->addHook('project_is_deleted', 'projectIsDeleted');
        $this->addHook(BlockScrumAccess::NAME);
        $this->addHook(OriginalProjectCollector::NAME);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(RedirectAfterArtifactCreationOrUpdateEvent::NAME);
        $this->addHook(BuildArtifactFormActionEvent::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);
        $this->addHook(AdditionalArtifactActionButtonsFetcher::NAME);
        $this->addHook(TrackerMasschangeGetExternalActionsEvent::NAME);
        $this->addHook(TrackerMasschangeProcessExternalActionsEvent::NAME);
        $this->addHook(GetExternalPostActionPluginsEvent::NAME);
        $this->addHook(GetExternalSubFactoriesEvent::NAME);
        $this->addHook(PostActionVisitExternalActionsEvent::NAME);
        $this->addHook(GetExternalPostActionJsonParserEvent::NAME);
        $this->addHook(GetWorkflowExternalPostActionsValueUpdater::NAME);
        $this->addHook(GetExternalSubFactoryByNameEvent::NAME);
        $this->addHook(ExternalPostActionSaveObjectEvent::NAME);
        $this->addHook(GetPostActionShortNameFromXmlTagNameEvent::NAME);
        $this->addHook(CheckPostActionsForTracker::NAME);
        $this->addHook(WorkflowDeletionEvent::NAME);
        $this->addHook(TransitionDeletionEvent::NAME);
        $this->addHook(CollectLinkedProjects::NAME);
        $this->addHook(ServiceDisabledCollector::NAME);
        $this->addHook(ProjectServiceBeforeActivation::NAME);
        $this->addHook(GetWhitelistedKeys::NAME);
        $this->addHook(DisplayCreatedProjectModalPresenter::NAME);
        $this->addHook(CollectCategorisedExternalTemplatesEvent::NAME);
        $this->addHook(ServiceEnableForXmlImportRetriever::NAME);
        $this->addHook(\Tuleap\Glyph\GlyphLocationsCollector::NAME);
        $this->addHook(ImportXMLProjectTrackerDone::NAME);
        $this->addHook(PossibleParentSelector::NAME);
        $this->addHook(HasCurrentProjectParentProjects::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard', 'cardwall'];
    }

    public function service_classnames(array &$params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['classnames'][$this->getServiceShortname()] = ProgramService::class;
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function getWhitelistedKeys(GetWhitelistedKeys $event): void
    {
        $event->addConfigClass(ForgeConfigAdapter::class);
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl(
                sprintf('/program_management/%s', urlencode($collector->getProject()->getUnixNameLowerCase()))
            );
        }
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $pluginInfo = new PluginInfo($this);
            $pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-program_management', 'Program Management'),
                    '',
                    dgettext(
                        'tuleap-program_management',
                        'Enables managing several related projects, synchronizing teams and milestones'
                    )
                )
            );
            $this->pluginInfo = $pluginInfo;
        }

        return $this->pluginInfo;
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            '/program_management',
            function (FastRoute\RouteCollector $r) {
                $r->get(
                    '/admin/{project_name:[A-z0-9-]+}[/]',
                    $this->getRouteHandler('routeGetAdminProgramManagement')
                );
                $r->get('/{project_name:[A-z0-9-]+}/increments/{increment_id:\d+}/plan[\]', $this->getRouteHandler('routeGetPlanIterations'));
                $r->get('/{project_name:[A-z0-9-]+}[/]', $this->getRouteHandler('routeGetProgramManagement'));
            }
        );
    }

    public function routeGetProgramManagement(): DisplayProgramBacklogController
    {
        $program_increments_dao = new ProgramIncrementsDAO();

        $user_manager    = UserManager::instance();
        $retrieve_user   = new UserManagerAdapter($user_manager);
        $project_manager = ProjectManager::instance();

        return new DisplayProgramBacklogController(
            $project_manager,
            new \Tuleap\Project\Flags\ProjectFlagsBuilder(new \Tuleap\Project\Flags\ProjectFlagsDao()),
            $this->getProgramAdapter(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../templates"),
            $this->getVisibleProgramIncrementTrackerRetriever($retrieve_user),
            $program_increments_dao,
            new TeamDao(),
            new PrioritizeFeaturesPermissionVerifier(
                $project_manager,
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new CanPrioritizeFeaturesDAO(),
                $retrieve_user
            ),
            new UserCanSubmitInTrackerVerifier($user_manager, TrackerFactory::instance())
        );
    }

    public function routeGetAdminProgramManagement(): DisplayAdminProgramManagementController
    {
        $project_manager = ProjectManager::instance();
        $program_dao     = new ProgramDao();
        $event_manager   = \EventManager::instance();

        $tracker_factory = TrackerFactory::instance();

        $user_manager         = UserManager::instance();
        $user_manager_adapter = new UserManagerAdapter($user_manager);

        $form_element_factory          = \Tracker_FormElementFactory::instance();
        $timeframe_dao                 = new SemanticTimeframeDao();
        $semantic_status_factory       = new Tracker_Semantic_StatusFactory();
        $logger                        = $this->getLogger();
        $planning_adapter              = new PlanningAdapter(\PlanningFactory::build(), $user_manager_adapter);
        $program_increments_dao        = new ProgramIncrementsDAO();
        $iteration_dao                 = new IterationsDAO();
        $retrieve_tracker_from_field   = new TrackerFromFieldRetriever($form_element_factory);
        $retrieve_project_from_tracker = new ProjectFromTrackerRetriever($tracker_factory);
        $gatherer                      = new SynchronizedFieldsGatherer(
            $tracker_factory,
            new \Tracker_Semantic_TitleFactory(),
            new \Tracker_Semantic_DescriptionFactory(),
            $semantic_status_factory,
            new SemanticTimeframeBuilder(
                $timeframe_dao,
                $form_element_factory,
                $tracker_factory,
                new LinksRetriever(
                    new ArtifactLinkFieldValueDao(),
                    Tracker_ArtifactFactory::instance()
                )
            ),
            $form_element_factory
        );

        $logger_message              = MessageLog::buildFromLogger($logger);
        $synchronized_fields_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $gatherer,
            $logger_message,
            $retrieve_tracker_from_field,
            new FieldPermissionsVerifier($user_manager_adapter, $form_element_factory),
            $retrieve_project_from_tracker
        );

        $checker = new TimeboxCreatorChecker(
            $synchronized_fields_builder,
            new SemanticsVerifier(
                new \Tracker_Semantic_TitleDao(),
                new \Tracker_Semantic_DescriptionDao(),
                $timeframe_dao,
                new StatusIsAlignedVerifier(new Tracker_Semantic_StatusDao(), $semantic_status_factory, $tracker_factory),
            ),
            new RequiredFieldVerifier($tracker_factory),
            new WorkflowVerifier(
                new Workflow_Dao(),
                new Tracker_Rule_Date_Dao(),
                new Tracker_Rule_List_Dao(),
                $tracker_factory
            ),
            $retrieve_tracker_from_field,
            $retrieve_project_from_tracker,
            new UserCanSubmitInTrackerVerifier($user_manager, $tracker_factory)
        );

        return new DisplayAdminProgramManagementController(
            new ProjectManagerAdapter($project_manager, $user_manager_adapter),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/admin'),
            new ProgramManagementBreadCrumbsBuilder(),
            $program_dao,
            new ProjectReferenceRetriever($project_manager),
            new TeamDao(),
            new ProgramAdapter(
                $project_manager,
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    $event_manager
                ),
                $program_dao,
                $user_manager_adapter
            ),
            $this->getVisibleProgramIncrementTrackerRetriever($user_manager_adapter),
            $this->getVisibleIterationTrackerRetriever($user_manager_adapter),
            new PotentialPlannableTrackersConfigurationPresentersBuilder(new PlanDao()),
            new ProjectUGroupCanPrioritizeItemsPresentersBuilder(
                new UGroupManagerAdapter($project_manager, new UGroupManager()),
                new CanPrioritizeFeaturesDAO(),
                new UGroupRepresentationBuilder()
            ),
            new ProjectPermissionVerifier($user_manager_adapter),
            new ProgramIncrementsDAO(),
            new TrackerFactoryAdapter($tracker_factory),
            new IterationsDAO(),
            $program_dao,
            new ForgeConfigAdapter(),
            new ConfigurationErrorPresenterBuilder(
                new ConfigurationErrorsGatherer(
                    $this->getProgramAdapter(),
                    new ProgramIncrementCreatorChecker(
                        $checker,
                        $program_increments_dao,
                        $planning_adapter,
                        $this->getVisibleProgramIncrementTrackerRetriever($user_manager_adapter),
                        $logger_message
                    ),
                    new IterationCreatorChecker(
                        $planning_adapter,
                        $iteration_dao,
                        $this->getVisibleIterationTrackerRetriever($user_manager_adapter),
                        $checker,
                        $logger_message
                    ),
                    new ProgramDao(),
                    new ProjectReferenceRetriever($project_manager)
                ),
                new PlanDao(),
                new TrackerSemantics($tracker_factory),
                $tracker_factory
            ),
            $project_manager
        );
    }

    public function routeGetPlanIterations(): DisplayPlanIterationsController
    {
        return new DisplayPlanIterationsController(
            ProjectManager::instance(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../templates"),
            new TeamDao()
        );
    }

    public function rootPlanningEditionEvent(RootPlanningEditionEvent $event): void
    {
        $handler = new RootPlanningEditionHandler(new TeamDao());
        $handler->handle(RootPlanningEditionEventProxy::buildFromEvent($event));
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES
     */
    public function getArtifactLinkNatures(array $params): void
    {
        $params['natures'][] = new TimeboxArtifactLinkPresenter();
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER
     */
    public function getNaturePresenter(array $params): void
    {
        if ($params['shortname'] === TimeboxArtifactLinkType::ART_LINK_SHORT_NAME) {
            $params['presenter'] = new TimeboxArtifactLinkPresenter();
        }
    }

    /**
     * @see Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES
     */
    public function trackerAddSystemNatures(array $params): void
    {
        $params['natures'][] = TimeboxArtifactLinkType::ART_LINK_SHORT_NAME;
    }

    public function canSubmitNewArtifact(CanSubmitNewArtifact $can_submit_new_artifact): void
    {
        $handler          = $this->getCanSubmitNewArtifactHandler();
        $errors_collector = new ConfigurationErrorsCollector(false);
        $handler->handle(CanSubmitNewArtifactEventProxy::buildFromEvent($can_submit_new_artifact), $errors_collector);
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $logger                 = $this->getLogger();
        $user_manager           = UserManager::instance();
        $user_retriever         = new UserManagerAdapter($user_manager);
        $program_increments_DAO = new ProgramIncrementsDAO();
        $artifact_factory       = \Tracker_ArtifactFactory::instance();
        $visibility_verifier    = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);
        $changeset_verifier     = new ChangesetDAO();
        $iterations_DAO         = new IterationsDAO();

        $creation_handler = new ProgramIncrementCreationEventHandler(
            MessageLog::buildFromLogger($logger),
            $program_increments_DAO,
            $visibility_verifier,
            $changeset_verifier,
            $program_increments_DAO,
            new ProgramIncrementCreationProcessorBuilder()
        );
        $creation_handler->handle(ProgramIncrementCreationEventProxy::fromWorkerEvent($logger, $user_manager, $event));

        $update_handler = new ProgramIncrementUpdateEventHandler(
            $program_increments_DAO,
            $iterations_DAO,
            new ProgramIncrementUpdateProcessorBuilder(),
            new IterationCreationProcessorBuilder()
        );
        $update_handler->handle(
            ProgramIncrementUpdateEventProxy::fromWorkerEvent(
                $logger,
                $user_retriever,
                $program_increments_DAO,
                $visibility_verifier,
                $iterations_DAO,
                $changeset_verifier,
                $event
            )
        );
    }

    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $logger  = $this->getLogger();
        $handler = new ArtifactCreatedHandler(
            new ArtifactsExplicitTopBacklogDAO(),
            new ProgramIncrementsDAO(),
            new ProgramIncrementCreationDispatcher(
                $logger,
                new QueueFactory($logger),
                new ProgramIncrementCreationProcessorBuilder()
            )
        );
        $handler->handle(ArtifactCreatedProxy::fromArtifactCreated($event));
    }

    public function trackerArtifactUpdated(ArtifactUpdated $event): void
    {
        $logger                         = $this->getLogger();
        $artifact_factory               = Tracker_ArtifactFactory::instance();
        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $user_retriever                 = new UserManagerAdapter(UserManager::instance());
        $iterations_linked_dao          = new IterationsLinkedToProgramIncrementDAO();
        $visibility_verifier            = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);
        $program_increments_DAO         = new ProgramIncrementsDAO();
        $mirrored_timeboxes_dao         = new MirroredTimeboxesDao();
        $transaction_executor           = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );

        $handler = new ArtifactUpdatedHandler(
            $program_increments_DAO,
            new UserStoriesInMirroredProgramIncrementsPlanner(
                $transaction_executor,
                $artifacts_linked_to_parent_dao,
                $artifact_factory,
                $mirrored_timeboxes_dao,
                $visibility_verifier,
                new ContentDao(),
                $logger,
                $user_retriever,
                $artifacts_linked_to_parent_dao
            ),
            new ArtifactsExplicitTopBacklogDAO(),
            new IterationCreationDetector(
                new ForgeConfigAdapter(),
                $iterations_linked_dao,
                $visibility_verifier,
                $iterations_linked_dao,
                MessageLog::buildFromLogger($logger),
                new LastChangesetRetriever($artifact_factory, Tracker_Artifact_ChangesetFactoryBuilder::build()),
                new IterationsDAO()
            ),
            new ProgramIncrementUpdateDispatcher(
                $logger,
                new QueueFactory($logger),
                new ProgramIncrementUpdateProcessorBuilder(),
                new IterationCreationProcessorBuilder()
            )
        );

        $event_proxy = ArtifactUpdatedProxy::fromArtifactUpdated($event);
        $handler->handle($event_proxy);
    }

    public function trackerArtifactDeleted(ArtifactDeleted $artifact_deleted): void
    {
        (new ArtifactsExplicitTopBacklogDAO())->removeArtifactsFromExplicitTopBacklog(
            [$artifact_deleted->getArtifact()->getID()]
        );
    }

    /**
     * @see         Event::REST_RESOURCES
     *
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function planningAdministrationDelegation(
        PlanningAdministrationDelegation $planning_administration_delegation
    ): void {
        $component_involved_verifier = $this->getComponentInvolvedVerifier();
        $project_data                = ProjectProxy::buildFromProject(
            $planning_administration_delegation->getProject()
        );
        if ($component_involved_verifier->isInvolvedInAProgramWorkspace($project_data)) {
            $planning_administration_delegation->enablePlanningAdministrationDelegation();
        }
    }

    public function trackerUsage(array $params): void
    {
        if ((new PlanDao())->isPartOfAPlan(TrackerReferenceProxy::fromTracker($params['tracker']))) {
            $params['result'] = [
                'can_be_deleted' => false,
                'message'        => $this->getPluginInfo()->getPluginDescriptor()->getFullName()
            ];
        }
    }

    public function projectIsDeleted(): void
    {
        (new WorkspaceDAO())->dropUnusedComponents();
    }

    public function externalParentCollector(OriginalProjectCollector $original_project_collector): void
    {
        $source_analyser = new SourceArtifactNatureAnalyzer(
            new MirroredTimeboxesDao(),
            Tracker_ArtifactFactory::instance(),
            new UserManagerAdapter(UserManager::instance())
        );
        $artifact        = $original_project_collector->getOriginalArtifact();
        $user            = $original_project_collector->getUser();
        $project_manager = ProjectManager::instance();

        try {
            $project_reference = $source_analyser->retrieveProjectOfMirroredArtifact(ArtifactIdentifierProxy::fromArtifact($artifact), UserProxy::buildFromPFUser($user));


            $project = $project_manager->getProject($project_reference->getId());
            $original_project_collector->setOriginalProject($project);
        } catch (NatureAnalyzerException $exception) {
            $logger = $this->getLogger();
            $logger->debug($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public function blockScrumAccess(BlockScrumAccess $block_scrum_access): void
    {
        $program_store = new ProgramDao();
        if ($program_store->isAProgram((int) $block_scrum_access->getProject()->getID())) {
            $block_scrum_access->disableScrumAccess();
        }
    }

    public function redirectAfterArtifactCreationOrUpdateEvent(RedirectAfterArtifactCreationOrUpdateEvent $event): void
    {
        $processor = new EventRedirectAfterArtifactCreationOrUpdateHandler();
        $processor->process($event->getRequest(), $event->getRedirect(), $event->getArtifact());
    }

    public function buildArtifactFormActionEvent(BuildArtifactFormActionEvent $event): void
    {
        $redirect_program_increment_value = $event->getRequest()->get('program_increment');
        $redirect_in_service              = $redirect_program_increment_value && ($redirect_program_increment_value === "create" || $redirect_program_increment_value === "update");
        if (! $redirect_in_service) {
            return;
        }

        $redirect = new RedirectParameterInjector();

        if ($redirect_program_increment_value === "update") {
            $redirect->injectAndInformUserAboutUpdatingProgramItem($event->getRedirect(), $GLOBALS['Response']);

            return;
        }

        $redirect->injectAndInformUserAboutProgramItem($event->getRedirect(), $GLOBALS['Response']);
    }

    public function additionalArtifactActionButtonsFetcher(AdditionalArtifactActionButtonsFetcher $event): void
    {
        $project_manager        = ProjectManager::instance();
        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            \EventManager::instance()
        );
        $assets                 = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/program_management',
            '/assets/program_management'
        );
        $user_manager_adapter   = new UserManagerAdapter(UserManager::instance());
        $action_builder         = new ArtifactTopBacklogActionBuilder(
            new ProgramAdapter(
                $project_manager,
                $project_access_checker,
                new ProgramDao(),
                $user_manager_adapter
            ),
            new PrioritizeFeaturesPermissionVerifier(
                $project_manager,
                $project_access_checker,
                new CanPrioritizeFeaturesDAO(),
                $user_manager_adapter
            ),
            new PlanDao(),
            new ArtifactsExplicitTopBacklogDAO(),
            new PlannedFeatureDAO(),
            new \Tuleap\Layout\JavascriptAsset($assets, 'artifact_additional_action.js'),
            new \Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerSemantics(TrackerFactory::instance())
        );

        $artifact = $event->getArtifact();
        $tracker  = $artifact->getTracker();

        $action = $action_builder->buildTopBacklogActionBuilder(
            new TopBacklogActionArtifactSourceInformation(
                $artifact->getId(),
                $tracker->getId(),
                (int) $tracker->getGroupId()
            ),
            $event->getUser()
        );

        if ($action !== null) {
            $event->addAction($action);
        }
    }

    public function trackerMasschangeGetExternalActionsEvent(TrackerMasschangeGetExternalActionsEvent $event): void
    {
        $project_manager        = ProjectManager::instance();
        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            \EventManager::instance()
        );
        $user_manager_adapter   = new UserManagerAdapter(UserManager::instance());
        $action_builder         = new MassChangeTopBacklogActionBuilder(
            new ProgramAdapter(
                $project_manager,
                $project_access_checker,
                new ProgramDao(),
                $user_manager_adapter
            ),
            new PrioritizeFeaturesPermissionVerifier(
                $project_manager,
                $project_access_checker,
                new CanPrioritizeFeaturesDAO(),
                $user_manager_adapter
            ),
            new PlanDao(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates')
        );

        $tracker = $event->getTracker();
        $action  = $action_builder->buildMassChangeAction(
            new TopBacklogActionMassChangeSourceInformation($tracker->getId(), (int) $tracker->getGroupId()),
            $event->getUser()
        );

        if ($action !== null) {
            $event->addExternalActions($action);
        }
    }

    public function trackerMasschangeProcessExternalActionsEvent(TrackerMasschangeProcessExternalActionsEvent $event): void
    {
        $processor = new MassChangeTopBacklogActionProcessor(
            $this->getProgramAdapter(),
            $this->getTopBacklogChangeProcessor()
        );

        $processor->processMassChangeAction(
            MassChangeTopBacklogSourceInformation::fromProcessExternalActionEvent($event)
        );
    }

    public function getExternalPostActionPluginsEvent(GetExternalPostActionPluginsEvent $event): void
    {
        $tracker_id = $event->getTracker()->getId();
        if ((new PlanDao())->isPlannable($tracker_id)) {
            $event->addServiceNameUsed($this->getServiceShortname());
        }
    }

    public function getExternalSubFactoriesEvent(GetExternalSubFactoriesEvent $event): void
    {
        $event->addFactory(
            $this->getAddToTopBacklogPostActionFactory()
        );
    }

    private function getAddToTopBacklogPostActionFactory(): AddToTopBacklogPostActionFactory
    {
        $dao = new AddToTopBacklogPostActionDAO();

        return new AddToTopBacklogPostActionFactory(
            $dao,
            $this->getProgramAdapter(),
            $this->getTopBacklogChangeProcessor(),
            $dao,
            $dao,
        );
    }

    private function getTopBacklogChangeProcessor(): TopBacklogChangeProcessor
    {
        $artifact_factory     = Tracker_ArtifactFactory::instance();
        $priority_manager     = \Tracker_Artifact_PriorityManager::build();
        $user_manager_adapter = new UserManagerAdapter(UserManager::instance());

        return new ProcessTopBacklogChange(
            new PrioritizeFeaturesPermissionVerifier(
                ProjectManager::instance(),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new CanPrioritizeFeaturesDAO(),
                $user_manager_adapter
            ),
            new ArtifactsExplicitTopBacklogDAO(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new FeaturesRankOrderer($priority_manager),
            new UserStoryLinkedToFeatureVerifier(
                new ArtifactsLinkedToParentDao(),
                new PlanningAdapter(\PlanningFactory::build(), $user_manager_adapter),
                $artifact_factory,
                $user_manager_adapter
            ),
            new VerifyIsVisibleFeatureAdapter($artifact_factory, $user_manager_adapter),
            new FeatureRemovalProcessor(
                new ProgramIncrementsDAO(),
                $artifact_factory,
                new ArtifactLinkUpdater($priority_manager, new ArtifactLinkUpdaterDataFormater()),
                $user_manager_adapter
            ),
        );
    }

    public function postActionVisitExternalActionsEvent(PostActionVisitExternalActionsEvent $event): void
    {
        $post_action = $event->getPostAction();

        if (! $post_action instanceof AddToTopBacklogPostAction) {
            return;
        }

        $representation = AddToTopBacklogPostActionRepresentation::buildFromPostAction($post_action);
        $event->setRepresentation($representation);
    }

    public function getExternalPostActionJsonParserEvent(GetExternalPostActionJsonParserEvent $event): void
    {
        $event->addParser(
            new AddToTopBacklogPostActionJSONParser(new PlanDao())
        );
    }

    public function getWorkflowExternalPostActionsValueUpdater(GetWorkflowExternalPostActionsValueUpdater $event): void
    {
        $dao = new AddToTopBacklogPostActionDAO();
        $event->addValueUpdater(
            new AddToTopBacklogPostActionValueUpdater(
                $dao,
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                $dao,
            )
        );
    }

    public function getExternalSubFactoryByNameEvent(GetExternalSubFactoryByNameEvent $event): void
    {
        if ($event->getPostActionShortName() === AddToTopBacklogPostAction::SHORT_NAME) {
            $event->setFactory(
                $this->getAddToTopBacklogPostActionFactory()
            );
        }
    }

    public function externalPostActionSaveObjectEvent(ExternalPostActionSaveObjectEvent $event): void
    {
        $post_action = $event->getPostAction();
        if (! $post_action instanceof AddToTopBacklogPostAction) {
            return;
        }

        $factory = $this->getAddToTopBacklogPostActionFactory();
        $factory->saveObject($post_action);
    }

    public function getPostActionShortNameFromXmlTagNameEvent(GetPostActionShortNameFromXmlTagNameEvent $event): void
    {
        if ($event->getXmlTagName() === AddToTopBacklogPostAction::XML_TAG_NAME) {
            $event->setPostActionShortName(AddToTopBacklogPostAction::SHORT_NAME);
        }
    }

    public function checkPostActionsForTracker(CheckPostActionsForTracker $event): void
    {
        $plan_store            = new PlanDao();
        $tracker               = $event->getTracker();
        $external_post_actions = $event->getPostActions()->getExternalPostActionsValue();
        foreach ($external_post_actions as $post_action) {
            if (
                $post_action instanceof AddToTopBacklogPostAction &&
                ! $plan_store->isPlannable($tracker->getId())
            ) {
                $message = dgettext(
                    'tuleap-program_management',
                    'The post action cannot be saved because this tracker is not a plannable tracker of a plan.'
                );

                $event->setErrorMessage($message);
                $event->setPostActionsNonEligible();
            }
        }
    }

    public function workflowDeletionEvent(WorkflowDeletionEvent $event): void
    {
        $workflow_id = (int) $event->getWorkflow()->getId();

        (new AddToTopBacklogPostActionDAO())->deleteWorkflowPostActions($workflow_id);
    }

    public function transitionDeletionEvent(TransitionDeletionEvent $event): void
    {
        $transition_id = (int) $event->getTransition()->getId();

        (new AddToTopBacklogPostActionDAO())->deleteTransitionPostActions($transition_id);
    }

    public function collectLinkedProjects(CollectLinkedProjects $event): void
    {
        $program_dao     = new ProgramDao();
        $team_dao        = new TeamDao();
        $project_manager = ProjectManager::instance();
        $handler         = new CollectLinkedProjectsHandler(
            $program_dao,
            $team_dao,
        );

        $event_proxy = CollectLinkedProjectsProxy::fromCollectLinkedProjects(
            new TeamsSearcher($program_dao, $project_manager),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                \EventManager::instance()
            ),
            new ProgramsSearcher($team_dao, $project_manager),
            $event
        );
        $handler->handle($event_proxy);
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger("program_management_syslog");
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event): void
    {
        $ugroup_manager                       = new UGroupManager();
        $permission_per_group_section_builder = new PermissionPerGroupSectionBuilder(
            new CanPrioritizeFeaturesDAO(),
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            $ugroup_manager,
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates')
        );

        $permission_per_group_section_builder->collectSections($event);
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $collector): void
    {
        $handler = new ServiceDisabledCollectorHandler(
            new TeamDao(),
            new ScrumBlocksServiceVerifier(
                PlanningFactory::build(),
                new UserManagerAdapter(UserManager::instance())
            )
        );
        $handler->handle(ServiceDisabledCollectorProxy::fromEvent($collector), $this->getServiceShortname());
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        $handler = new ProjectServiceBeforeActivationHandler(
            new TeamDao(),
            new ScrumBlocksServiceVerifier(PlanningFactory::build(), new UserManagerAdapter(UserManager::instance()))
        );

        $handler->handle(ProjectServiceBeforeActivationProxy::fromEvent($event), $this->getServiceShortname());
    }

    private function getComponentInvolvedVerifier(): ComponentInvolvedVerifier
    {
        $component_involved_verifier = new ComponentInvolvedVerifier(
            new TeamDao(),
            new ProgramDao()
        );

        return $component_involved_verifier;
    }

    private function getProgramAdapter(): ProgramAdapter
    {
        return new ProgramAdapter(
            ProjectManager::instance(),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                \EventManager::instance()
            ),
            new ProgramDao(),
            new UserManagerAdapter(UserManager::instance())
        );
    }

    private function getCanSubmitNewArtifactHandler(): CanSubmitNewArtifactHandler
    {
        $user_manager                  = UserManager::instance();
        $retrieve_user                 = new UserManagerAdapter($user_manager);
        $form_element_factory          = \Tracker_FormElementFactory::instance();
        $timeframe_dao                 = new SemanticTimeframeDao();
        $semantic_status_factory       = new Tracker_Semantic_StatusFactory();
        $logger                        = $this->getLogger();
        $planning_adapter              = new PlanningAdapter(\PlanningFactory::build(), $retrieve_user);
        $program_increments_dao        = new ProgramIncrementsDAO();
        $tracker_factory               = \TrackerFactory::instance();
        $iteration_dao                 = new IterationsDAO();
        $retrieve_tracker_from_field   = new TrackerFromFieldRetriever($form_element_factory);
        $retrieve_project_from_tracker = new ProjectFromTrackerRetriever($tracker_factory);

        $gatherer = new SynchronizedFieldsGatherer(
            $tracker_factory,
            new \Tracker_Semantic_TitleFactory(),
            new \Tracker_Semantic_DescriptionFactory(),
            $semantic_status_factory,
            new SemanticTimeframeBuilder(
                $timeframe_dao,
                $form_element_factory,
                $tracker_factory,
                new LinksRetriever(
                    new ArtifactLinkFieldValueDao(),
                    Tracker_ArtifactFactory::instance()
                )
            ),
            $form_element_factory
        );

        $logger_message              = MessageLog::buildFromLogger($logger);
        $synchronized_fields_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $gatherer,
            $logger_message,
            $retrieve_tracker_from_field,
            new FieldPermissionsVerifier($retrieve_user, $form_element_factory),
            $retrieve_project_from_tracker
        );

        $checker = new TimeboxCreatorChecker(
            $synchronized_fields_builder,
            new SemanticsVerifier(
                new \Tracker_Semantic_TitleDao(),
                new \Tracker_Semantic_DescriptionDao(),
                $timeframe_dao,
                new StatusIsAlignedVerifier(new Tracker_Semantic_StatusDao(), $semantic_status_factory, $tracker_factory),
            ),
            new RequiredFieldVerifier($tracker_factory),
            new WorkflowVerifier(
                new Workflow_Dao(),
                new Tracker_Rule_Date_Dao(),
                new Tracker_Rule_List_Dao(),
                $tracker_factory
            ),
            $retrieve_tracker_from_field,
            $retrieve_project_from_tracker,
            new UserCanSubmitInTrackerVerifier($user_manager, $tracker_factory)
        );


        return new CanSubmitNewArtifactHandler(
            new ConfigurationErrorsGatherer(
                $this->getProgramAdapter(),
                new ProgramIncrementCreatorChecker(
                    $checker,
                    $program_increments_dao,
                    $planning_adapter,
                    $this->getVisibleProgramIncrementTrackerRetriever($retrieve_user),
                    $logger_message
                ),
                new IterationCreatorChecker(
                    $planning_adapter,
                    $iteration_dao,
                    $this->getVisibleIterationTrackerRetriever($retrieve_user),
                    $checker,
                    $logger_message
                ),
                new ProgramDao(),
                new ProjectReferenceRetriever(ProjectManager::instance()),
            )
        );
    }

    private function getVisibleProgramIncrementTrackerRetriever(UserManagerAdapter $retrieve_user): VisibleProgramIncrementTrackerRetriever
    {
        return new VisibleProgramIncrementTrackerRetriever(
            new ProgramIncrementsDAO(),
            TrackerFactory::instance(),
            $retrieve_user
        );
    }


    private function getVisibleIterationTrackerRetriever(UserManagerAdapter $retrieve_user): VisibleIterationTrackerRetriever
    {
        return new VisibleIterationTrackerRetriever(
            new IterationsDAO(),
            \TrackerFactory::instance(),
            $retrieve_user
        );
    }

    public function displayCreatedProjectModal(DisplayCreatedProjectModalPresenter $presenter): void
    {
        if (
            $presenter->should_display_created_project_modal &&
            $presenter->getXmlTemplateName() === 'program_management_program'
        ) {
            $presenter->setCustomPrimaryAction(
                dgettext('tuleap-program_management', 'Configure the teams'),
                '/program_management/admin/' . $presenter->getProject()->getUnixNameLowerCase()
            );
        }
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    public function collectCategorisedExternalTemplatesEvent(CollectCategorisedExternalTemplatesEvent $event): void
    {
        $event_manager       = EventManager::instance();
        $glyph_finder        = new GlyphFinder($event_manager);
        $consistency_checker = new ConsistencyChecker(
            new XMLFileContentRetriever(),
            $event_manager,
            new ServiceEnableForXmlImportRetriever(\PluginFactory::instance())
        );
        $event->addCategorisedTemplate(
            new ProgramTemplate(
                $glyph_finder,
                $consistency_checker
            )
        );
        $event->addCategorisedTemplate(
            new TeamTemplate(
                $glyph_finder,
                $consistency_checker
            )
        );
    }

    public function collectGlyphLocations(GlyphLocationsCollector $glyph_locations_collector): void
    {
        $glyph_locations_collector->addLocation(
            'tuleap-program-management',
            new GlyphLocation(__DIR__ . '/../glyphs')
        );
    }

    public function importXMLProjectTrackerDone(ImportXMLProjectTrackerDone $event): void
    {
        $retrieve_user = new UserManagerAdapter(UserManager::instance());
        $importer      = new ProgramManagementConfigXMLImporter(
            new PlanCreator(
                new TrackerFactoryAdapter(\TrackerFactory::instance()),
                new ProgramUserGroupRetriever(new UserGroupRetriever(new \UGroupManager())),
                new PlanDao(),
                new ProjectManagerAdapter(\ProjectManager::instance(), $retrieve_user),
                new TeamDao(),
                new ProjectPermissionVerifier($retrieve_user),
            ),
            new ProgramManagementXMLConfigParser(),
            new ProgramManagementXMLConfigExtractor(
                new UGroupManagerAdapter(ProjectManager::instance(), new UGroupManager())
            ),
            $event->getLogger()
        );

        $user_identifier = UserProxy::buildFromPFUser($event->getUser());
        $importer->import(
            ProgramForAdministrationIdentifier::fromProject(
                new TeamDao(),
                new ProjectPermissionVerifier($retrieve_user),
                $user_identifier,
                ProjectProxy::buildFromProject($event->getProject())
            ),
            $event->getExtractionPath(),
            $event->getCreatedTrackersMapping(),
            $user_identifier
        );
    }

    public function trackerArtifactPossibleParentSelector(PossibleParentSelector $possible_parent_selector): void
    {
        $project_manager        = ProjectManager::instance();
        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            \EventManager::instance()
        );

        $user_manager_adapter = new UserManagerAdapter(UserManager::instance());

        $features_dao = new FeaturesDao();
        (new PossibleParentHandler(
            new VerifyIsVisibleFeatureAdapter(
                Tracker_ArtifactFactory::instance(),
                $user_manager_adapter
            ),
            new ProgramAdapter(
                $project_manager,
                $project_access_checker,
                new ProgramDao(),
                $user_manager_adapter
            ),
            new TeamDao(),
            $features_dao,
            $features_dao,
        ))->handle(
            PossibleParentSelectorProxy::fromEvent(
                $possible_parent_selector,
                PlanningFactory::build(),
                Tracker_ArtifactFactory::instance(),
            )
        );
    }

    public function hasCurrentProjectParentProjects(HasCurrentProjectParentProjects $event): void
    {
        $dao     = new TeamDao();
        $project = ProjectProxy::buildFromProject($event->getProject());

        if ($dao->isATeam($project->getId())) {
            $event->setHasParents();
        }
    }
}
