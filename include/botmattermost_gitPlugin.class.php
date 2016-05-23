<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once 'autoload.php';
require_once 'constants.php';

use Tuleap\BotMattermostGit\BotGit\BotGitFactory;
use Tuleap\BotMattermostGit\BotGit\BotGitDao;
use Tuleap\BotMattermostGit\Controller;
use Tuleap\BotMattermostGit\Plugin\PluginInfo;
use Tuleap\BotMattermostGit\SenderServices\EncoderMessage;
use Tuleap\BotMattermostGit\SenderServices\NotificationMaker;
use Tuleap\BotMattermostGit\SenderServices\Sender;


class botmattermost_gitPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->addHook('cssfile');
        if (defined('GIT_BASE_URL')) {
            $this->addHook(GIT_ADDITIONAL_NOTIFICATIONS);
            $this->addHook(GIT_HOOK_POSTRECEIVE_REF_UPDATE);
        }
    }

    public function getDependencies()
    {
        return array('git', 'botmattermost');
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname()
    {
        return 'plugin_botmattermost_git';
    }

    public function git_additional_notifications(array $params)
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $render = $this->getController($params['request'])->render($params['repository']);
            $params['output'] .= $render;
        }
    }

    public function git_hook_post_receive_ref_update($params)
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $request = HTTPRequest::instance();
            $this->getController($request)->sendNotification(
                $params['repository'],
                $params['user'],
                $params['newrev'],
                $params['refname']
            );
        }
    }

    public function cssfile($params)
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if (strpos($_SERVER['REQUEST_URI'], $git_plugin->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function process()
    {
        $request = HTTPRequest::instance();
        if ($this->isAllowed($request->getProject()->getID())) {
            $this->getController($request)->save();
        }
    }

    private function getController(Codendi_Request $request)
    {
        return new Controller(
            $request,
            new CSRFSynchronizerToken('/plugins/botmattermost_git/?group_id='.$request->getProject()->getID()),
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            new BotGitFactory(new BotGitDao()),
            new Sender(
                new EncoderMessage(),
                new NotificationMaker(new Git_GitRepositoryUrlManager(PluginManager::instance()->getPluginByName('git')))
            )
        );
    }
}
