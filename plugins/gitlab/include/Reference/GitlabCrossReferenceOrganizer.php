<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use Project;
use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitFactory;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequest;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Reference\AdditionalBadgePresenter;
use Tuleap\Reference\CreationMetadataPresenter;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;

class GitlabCrossReferenceOrganizer
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;
    /**
     * @var GitlabCommitFactory
     */
    private $gitlab_commit_factory;
    /**
     * @var GitlabCommitCrossReferenceEnhancer
     */
    private $gitlab_cross_reference_enhancer;
    /**
     * @var GitlabMergeRequestReferenceRetriever
     */
    private $gitlab_merge_request_reference_retriever;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $relative_date_builder;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \UserHelper
     */
    private $user_helper;

    public function __construct(
        GitlabRepositoryFactory $gitlab_repository_factory,
        GitlabCommitFactory $gitlab_commit_factory,
        GitlabCommitCrossReferenceEnhancer $gitlab_cross_reference_enhancer,
        GitlabMergeRequestReferenceRetriever $gitlab_merge_request_reference_retriever,
        \ProjectManager $project_manager,
        TlpRelativeDatePresenterBuilder $relative_date_builder,
        \UserManager $user_manager,
        \UserHelper $user_helper
    ) {
        $this->gitlab_repository_factory                = $gitlab_repository_factory;
        $this->gitlab_commit_factory                    = $gitlab_commit_factory;
        $this->gitlab_cross_reference_enhancer          = $gitlab_cross_reference_enhancer;
        $this->gitlab_merge_request_reference_retriever = $gitlab_merge_request_reference_retriever;
        $this->project_manager                          = $project_manager;
        $this->relative_date_builder                    = $relative_date_builder;
        $this->user_manager                             = $user_manager;
        $this->user_helper                              = $user_helper;
    }

    public function organizeGitLabReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if (
                $cross_reference_presenter->type === GitlabCommitReference::NATURE_NAME ||
                $cross_reference_presenter->type === GitlabMergeRequestReference::NATURE_NAME
            ) {
                $this->moveGitlabCrossReferenceToRepositorySection($by_nature_organizer, $cross_reference_presenter);
            }
        }
    }

    private function moveGitlabCrossReferenceToRepositorySection(
        CrossReferenceByNatureOrganizer $by_nature_organizer,
        CrossReferencePresenter $cross_reference_presenter
    ): void {
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        [$repository_name, $item_id] = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            $cross_reference_presenter->target_value
        );

        if (! $repository_name || ! $item_id) {
            return;
        }

        $repository = $this->gitlab_repository_factory->getGitlabRepositoryByNameInProject(
            $project,
            $repository_name
        );

        if ($repository === null) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        if ($cross_reference_presenter->type === GitlabCommitReference::NATURE_NAME) {
            $this->moveGitlabCommitCrossReferenceToRepositorySection(
                $by_nature_organizer,
                $cross_reference_presenter,
                $project,
                $repository,
                $item_id
            );
        }

        if ($cross_reference_presenter->type === GitlabMergeRequestReference::NATURE_NAME) {
            $this->moveGitlabMergeRequestCrossReferenceToRepositorySection(
                $by_nature_organizer,
                $cross_reference_presenter,
                $project,
                $repository,
                (int) $item_id
            );
        }
    }

    private function moveGitlabCommitCrossReferenceToRepositorySection(
        CrossReferenceByNatureOrganizer $by_nature_organizer,
        CrossReferencePresenter $cross_reference_presenter,
        Project $project,
        GitlabRepository $repository,
        string $sha1
    ): void {
        $user = $by_nature_organizer->getCurrentUser();

        $commit_info = $this->gitlab_commit_factory->getGitlabCommitInRepositoryWithSha1(
            $repository,
            $sha1
        );

        if ($commit_info === null) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $by_nature_organizer->moveCrossReferenceToSection(
            $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
                $cross_reference_presenter,
                $commit_info,
                $user
            ),
            $project->getUnixNameLowerCase() . '/' . $repository->getName()
        );
    }

    private function moveGitlabMergeRequestCrossReferenceToRepositorySection(
        CrossReferenceByNatureOrganizer $by_nature_organizer,
        CrossReferencePresenter $cross_reference_presenter,
        Project $project,
        GitlabRepository $repository,
        int $id
    ): void {
        $gitlab_merge_request = $this->gitlab_merge_request_reference_retriever->getGitlabMergeRequestInRepositoryWithId(
            $repository,
            $id
        );

        if ($gitlab_merge_request === null) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $additional_badge_presenters = $this->getMergeRequestAdditionalBadges($gitlab_merge_request);
        $user                        = $by_nature_organizer->getCurrentUser();

        $by_nature_organizer->moveCrossReferenceToSection(
            $cross_reference_presenter
                ->withTitle($gitlab_merge_request->getTitle(), null)
                ->withCreationMetadata(
                    $this->getCreatedByPresenter($gitlab_merge_request),
                    $this->getCreatedOnPresenter($gitlab_merge_request, $user)
                )
                ->withAdditionalBadges($additional_badge_presenters),
            $project->getUnixNameLowerCase() . '/' . $repository->getName()
        );
    }

    /**
     * @return AdditionalBadgePresenter[]
     */
    private function getMergeRequestAdditionalBadges(GitlabMergeRequest $gitlab_merge_request): array
    {
        switch ($gitlab_merge_request->getState()) {
            case 'opened':
                return [AdditionalBadgePresenter::buildSecondary(dgettext('tuleap-gitlab', 'Open'))];
            case 'merged':
                return [AdditionalBadgePresenter::buildSuccess(dgettext('tuleap-gitlab', 'Merged'))];
            case 'closed':
                return [AdditionalBadgePresenter::buildDanger(dgettext('tuleap-gitlab', 'Closed'))];
        }

        return [];
    }

    private function getCreatedOnPresenter(GitlabMergeRequest $gitlab_merge_request, \PFUser $user): TlpRelativeDatePresenter
    {
        return $this->relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
            $gitlab_merge_request->getCreatedAtDate(),
            $user
        );
    }

    private function getCreatedByPresenter(GitlabMergeRequest $gitlab_merge_request): ?CreatedByPresenter
    {
        $author_email = $gitlab_merge_request->getAuthorEmail() ?? "";
        $author_name  = $gitlab_merge_request->getAuthorName() ?? "";

        if (! $author_email && ! $author_name) {
            return CreationMetadataPresenter::NO_CREATED_BY_PRESENTER;
        }

        if (! $author_email) {
            return new CreatedByPresenter(
                $author_name,
                false,
                ''
            );
        }

        $tuleap_user = $this->user_manager->getUserByEmail($author_email);

        if ($tuleap_user === null) {
            return new CreatedByPresenter(
                $author_name,
                false,
                ''
            );
        }

        return new CreatedByPresenter(
            trim($this->user_helper->getDisplayNameFromUser($tuleap_user) ?? ''),
            $tuleap_user->hasAvatar(),
            $tuleap_user->getAvatarUrl()
        );
    }
}
