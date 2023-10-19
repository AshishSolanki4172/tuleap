<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\SVNCore\ViewVC;

require_once __DIR__ . '/../../../www/include/viewvc_utils.php';
require_once __DIR__ . '/../../../www/svn/svn_utils.php';

use HTTPRequest;
use Project;
use Codendi_HTMLPurifier;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\SVNCore\Event\GetSVNLoginNameEvent;

class ViewVCProxy
{
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager, private CheckProjectAccess $check_project_access)
    {
        $this->event_manager = $event_manager;
    }

    private function displayViewVcHeader(HTTPRequest $request)
    {
        $request_uri = $request->getFromServer('REQUEST_URI');

        if (strpos($request_uri, "annotate=") !== false) {
            return true;
        }

        if (
            $this->isViewingPatch($request) ||
            $this->isCheckoutingFile($request) ||
            strpos($request_uri, "view=graphimg") !== false ||
            strpos($request_uri, "view=redirect_path") !== false ||
            // ViewVC will redirect URLs with "&rev=" to "&revision=". This is needed by Hudson.
            strpos($request_uri, "&rev=") !== false
        ) {
            return false;
        }

        if (
            strpos($request_uri, "/?") === false &&
            strpos($request_uri, "&r1=") === false &&
            strpos($request_uri, "&r2=") === false &&
            strpos($request_uri, "view=") === false
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isViewingPatch(HTTPRequest $request)
    {
        $request_uri = $request->getFromServer('REQUEST_URI');
        return strpos($request_uri, "view=patch") !== false;
    }

    /**
     * @return bool
     */
    private function isCheckoutingFile(HTTPRequest $request)
    {
        $request_uri = $request->getFromServer('REQUEST_URI');
        return strpos($request_uri, "view=co") !== false;
    }

    private function buildQueryString(HTTPRequest $request)
    {
        parse_str($request->getFromServer('QUERY_STRING'), $query_string_parts);
        unset($query_string_parts['roottype']);
        return http_build_query($query_string_parts);
    }

    private function escapeStringFromServer(HTTPRequest $request, $key)
    {
        $string = $request->getFromServer($key);

        return escapeshellarg($string);
    }

    private function setLocaleOnFileName($path)
    {
        $current_locales = setlocale(LC_ALL, "0");
        // to allow $path filenames with French characters
        setlocale(LC_CTYPE, "en_US.UTF-8");

        $encoded_path = escapeshellarg($path);
        setlocale(LC_ALL, $current_locales);

        return $encoded_path;
    }

    private function setLocaleOnCommand($command, &$return_var)
    {
        ob_start();
        putenv("LC_CTYPE=en_US.UTF-8");
        passthru($command, $return_var);

        return ob_get_clean();
    }

    private function getViewVcLocationHeader($location_line)
    {
        // Now look for 'Location:' header line (e.g. generated by 'view=redirect_pathrev'
        // parameter, used when browsing a directory at a certain revision number)
        $location_found = false;

        while ($location_line && ! $location_found && strlen($location_line) > 1) {
            $matches = [];

            if (preg_match('/^Location:(.*)$/', $location_line, $matches)) {
                return trim($matches[1]);
            }

            $location_line = strtok("\n\t\r\0\x0B");
        }

        return false;
    }

    /**
     * @return string
     */
    private function getUsername(\PFUser $user, Project $project)
    {
        $event = new GetSVNLoginNameEvent($user, $project);
        $this->event_manager->processEvent($event);
        return $event->getUsername();
    }

    /**
     * @return string
     */
    private function getPythonLauncher()
    {
        if (file_exists('/opt/rh/python27/root/usr/bin/python')) {
            return "LD_LIBRARY_PATH='/opt/rh/python27/root/usr/lib64' /opt/rh/python27/root/usr/bin/python";
        }
        return '/usr/bin/python';
    }

    public function displayContent(Project $project, HTTPRequest $request, string $path)
    {
        $user = $request->getCurrentUser();

        try {
            $this->check_project_access->checkUserCanAccessProject($user, $project);
        } catch (\Project_AccessException $exception) {
            $this->display($project, $path, $this->getPermissionDeniedError($project));
            return;
        }

        viewvc_utils_track_browsing($project->getID(), 'svn');

        $command = 'REMOTE_USER_ID=' . escapeshellarg($user->getId()) . ' ' .
            'REMOTE_USER=' . escapeshellarg($this->getUsername($user, $project)) . ' ' .
            'PATH_INFO=' . $this->setLocaleOnFileName($path) . ' ' .
            'QUERY_STRING=' . escapeshellarg($this->buildQueryString($request)) . ' ' .
            'SCRIPT_NAME=/svn/viewvc.php ' .
            'HTTP_ACCEPT_ENCODING=' . $this->escapeStringFromServer($request, 'HTTP_ACCEPT_ENCODING') . ' ' .
            'HTTP_ACCEPT_LANGUAGE=' . $this->escapeStringFromServer($request, 'HTTP_ACCEPT_LANGUAGE') . ' ' .
            'TULEAP_PROJECT_NAME=' . escapeshellarg($project->getUnixNameMixedCase()) . ' ' .
            'TULEAP_REPO_NAME=' . escapeshellarg($project->getUnixNameMixedCase()) . ' ' .
            'TULEAP_REPO_PATH=' . escapeshellarg($project->getSVNRootPath()) . ' ' .
            'TULEAP_USER_IS_SUPER_USER=' . escapeshellarg($user->isSuperUser() ? '1' : '0') . ' ' .
            $this->getPythonLauncher() . ' ' . __DIR__ . '/viewvc-epel.cgi 2>&1';

        $content = $this->setLocaleOnCommand($command, $return_var);

        if ($return_var === 128) {
            $this->display($project, $path, $this->getPermissionDeniedError($project));
            return;
        }

        list($headers, $body) = http_split_header_body($content);

        $content_type_line = strtok($content, "\n\t\r\0\x0B");

        $content = substr($content, strpos($content, $content_type_line));

        $location_line   = strtok($content, "\n\t\r\0\x0B");
        $viewvc_location = $this->getViewVcLocationHeader($location_line);

        if ($viewvc_location) {
            $GLOBALS['Response']->redirect($viewvc_location);
        }

        $parse = $this->displayViewVcHeader($request);
        if ($parse) {
            $this->display($project, $path, $body);
        } else {
            if ($this->isViewingPatch($request)) {
                header('Content-Type: text/plain');
            } else {
                header('Content-Type: application/octet-stream');
            }
            header('X-Content-Type-Options: nosniff');
            header('Content-Disposition: attachment');

            echo $body;
            exit();
        }
    }

    private function display(Project $project, $path, $body)
    {
        svn_header(
            $project,
            HeaderConfigurationBuilder::get($GLOBALS['Language']->getText('svn_utils', 'browse_tree'))
                ->inProject($project, \Service::SVN)
                ->withBodyClass(['viewvc-epel'])
                ->build(),
            urlencode($path)
        );
        echo util_make_reference_links(
            $body,
            $project->getID()
        );
        site_footer([]);
    }

    private function getPermissionDeniedError(Project $project)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $url      = session_make_url("/project/memberlist.php?group_id=" . urlencode((string) $project->getID()));

        $title  = $purifier->purify($GLOBALS['Language']->getText('svn_viewvc', 'access_denied'));
        $reason = $GLOBALS['Language']->getText('svn_viewvc', 'acc_den_comment', $purifier->purify($url));

        return '<link rel="stylesheet" href="/viewvc-theme-tuleap/style.css">
            <div class="tuleap-viewvc-header">
                <h3>' . $title . '</h3>
                ' . $reason . '
            </div>';
    }
}
