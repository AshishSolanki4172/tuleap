<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc;

use HTTPRequest;
use Psr\Log\LoggerInterface;
use Tuleap\Artidoc\Document\ArtidocBreadcrumbsProvider;
use Tuleap\Artidoc\Document\ArtidocDocumentInformation;
use Tuleap\Artidoc\Document\RetrieveArtidoc;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final readonly class ArtidocController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private RetrieveArtidoc $retrieve_artidoc,
        private ArtidocBreadcrumbsProvider $breadcrumbs_provider,
        private LoggerInterface $logger,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        ServiceInstrumentation::increment('artidoc');

        $this->retrieve_artidoc->retrieveArtidoc((int) $variables['id'], $request->getCurrentUser())
            ->match(
                fn (ArtidocDocumentInformation $document_information) => $this->renderPage($document_information, $layout, $request->getCurrentUser()),
                function (Fault $fault) {
                    Fault::writeToLogger($fault, $this->logger);
                    throw new NotFoundException();
                }
            );
    }

    private function renderPage(ArtidocDocumentInformation $document_information, BaseLayout $layout, \PFUser $user): void
    {
        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../scripts/artidoc/frontend-assets',
                    '/assets/artidoc/artidoc'
                ),
                'src/index.ts'
            )
        );

        $title   = $document_information->document->getTitle();
        $service = $document_information->service_docman;

        $permissions_manager = \Docman_PermissionsManager::instance((int) $service->getProject()->getID());
        $user_can_write      = $permissions_manager->userCanWrite($user, $document_information->document->getId());

        $service->displayHeader($title, $this->breadcrumbs_provider->getBreadcrumbs($document_information, $user), []);
        \TemplateRendererFactory::build()->getRenderer(__DIR__)->renderToPage('artidoc', [
            'item_id' => $document_information->document->getId(),
            'can_user_edit_document' => $user_can_write,
            'title' => $title,
        ]);
        $service->displayFooter();
    }
}
