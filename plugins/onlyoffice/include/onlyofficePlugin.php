<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\Download\DocmanFileDownloadController;
use Tuleap\Docman\Download\DocmanFileDownloadResponseGenerator;
use Tuleap\Docman\REST\v1\OpenItemHref;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Layout\BaseLayout;
use Tuleap\OnlyOffice\Administration\OnlyOfficeAdminSettingsController;
use Tuleap\OnlyOffice\Administration\OnlyOfficeAdminSettingsPresenter;
use Tuleap\OnlyOffice\Administration\OnlyOfficeDocumentServerSettings;
use Tuleap\OnlyOffice\Download\DownloadDocumentWithTokenMiddleware;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenDAO;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenVerifier;
use Tuleap\OnlyOffice\Download\PrefixOnlyOfficeDocumentDownload;
use Tuleap\OnlyOffice\Open\AllowedFileExtensions;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../../docman/include/docmanPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class onlyofficePlugin extends Plugin implements PluginWithConfigKeys
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-onlyoffice', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-onlyoffice', 'ONLYOFFICE integration'),
                    dgettext('tuleap-onlyoffice', 'Allow to open documents into ONLYOFFICE')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['docman'];
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(OpenItemHref::NAME);
        $this->addHook(SiteAdministrationAddOption::NAME);
        return parent::getHooksAndCallbacks();
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(OnlyOfficeDocumentServerSettings::class);
    }

    public function collectRoutesEvent(CollectRoutesEvent $routes): void
    {
        $route_collector = $routes->getRouteCollector();
        $route_collector->addGroup(
            '/onlyoffice',
            function (FastRoute\RouteCollector $r): void {
                $r->get('/document_download', $this->getRouteHandler('routeGetDocumentDownload'));
                $r->get('/open/{id:\d+}', $this->getRouteHandler('routeGetOpenOnlyOffice'));
            }
        );
        $route_collector->get(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL, $this->getRouteHandler('routeGetAdminSettings'));
        $route_collector->post(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL, $this->getRouteHandler('routePostAdminSettings'));
    }

    public function routeGetDocumentDownload(): DocmanFileDownloadController
    {
        $logger           = BackendLogger::getDefaultLogger();
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $token_middleware = new DownloadDocumentWithTokenMiddleware(
            new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentDownload()),
            new OnlyOfficeDownloadDocumentTokenVerifier(
                new OnlyOfficeDownloadDocumentTokenDAO(),
                new DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection()),
                new SplitTokenVerificationStringHasher(),
                $logger,
            ),
            UserManager::instance()
        );
        return new DocmanFileDownloadController(
            new SapiStreamEmitter(),
            new Docman_ItemFactory(),
            new DocmanFileDownloadResponseGenerator(
                new Docman_VersionFactory(),
                new BinaryFileResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory())
            ),
            $token_middleware,
            $logger,
            new SessionWriteCloseMiddleware(),
            $token_middleware,
        );
    }

    public function openItemHref(OpenItemHref $open_item_href): void
    {
        if (AllowedFileExtensions::isFilenameAllowedToBeOpenInOnlyOffice($open_item_href->getVersion()->getFilename())) {
            $open_item_href->setHref(
                '/onlyoffice/open/' . urlencode((string) $open_item_href->getItem()->getId())
            );
        }
    }

    public function routeGetOpenOnlyOffice(): \Tuleap\Request\DispatchableWithRequest
    {
        return new class implements \Tuleap\Request\DispatchableWithRequest {
            public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
            {
                echo "Opening documents in OnlyOffice is not implemented yet";
            }
        };
    }

    public function routeGetAdminSettings(): OnlyOfficeAdminSettingsController
    {
        return new OnlyOfficeAdminSettingsController(
            new AdminPageRenderer(),
            UserManager::instance(),
            new OnlyOfficeAdminSettingsPresenter(
                ForgeConfig::get(OnlyOfficeDocumentServerSettings::URL, ''),
                ForgeConfig::exists(OnlyOfficeDocumentServerSettings::SECRET),
                CSRFSynchronizerTokenPresenter::fromToken(self::buildCSRFTokenAdmin()),
            )
        );
    }

    public function routePostAdminSettings(): \Tuleap\OnlyOffice\Administration\OnlyOfficeSaveAdminSettingsController
    {
        return new \Tuleap\OnlyOffice\Administration\OnlyOfficeSaveAdminSettingsController(
            self::buildCSRFTokenAdmin(),
            new \Tuleap\Config\ConfigSet(EventManager::instance(), new \Tuleap\Config\ConfigDao()),
            new Valid_HTTPSURI(),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())),
            new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    private static function buildCSRFTokenAdmin(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL);
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-onlyoffice', 'ONLYOFFICE'),
                OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL
            )
        );
    }
}
