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

namespace Tuleap\BotMattermost;

use HTTPRequest;
use CSRFSynchronizerToken;
use TemplateRendererFactory;
use Feedback;
use Valid_HTTPURI;
use Valid_Numeric;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\CannotDeleteBotException;
use Tuleap\BotMattermost\Exception\CannotUpdateBotException;
use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;
use Tuleap\BotMattermost\AdminPresenter;

class AdminController
{

    private $csrf;
    private $bot_factory;

    public function __construct(CSRFSynchronizerToken $csrf, BotFactory $bot_factory)
    {
        $this->csrf        = $csrf;
        $this->bot_factory = $bot_factory;
    }

    public function process(HTTPRequest $request)
    {
        if ($request->getCurrentUser()->isSuperUser()) {

            $action = $request->get('action');
            switch ($action) {
                case 'add_bot':
                    $this->addBot($request);
                    break;
                case 'edit_bot':
                    $this->editBot($request);
                    break;
                case 'delete_bot':
                    $this->deleteBot($request);
                    break;
                default:
                    $this->displayIndex();
            }
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('include_session', 'insufficient_u_access', 'Insufficient User Access'));
            $GLOBALS['Response']->redirect('/');
        }
    }

    private function displayIndex()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(PLUGIN_BOT_MATTERMOST_BASE_DIR.'/template');
        try {
            $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('plugin_botmattermost', 'descriptor_name')));
            $renderer->renderToPage('index', new AdminPresenter($this->csrf, $this->bot_factory->getBots()));
            $GLOBALS['HTML']->footer(array());
        } catch (BotNotFoundException $e) {
            $this->redirectToAdminSectionWithErrorFeedback($e);
        } catch (ChannelsNotFoundException $e) {
            $this->redirectToAdminSectionWithErrorFeedback($e);
        }
    }

    private function addBot(HTTPRequest $request)
    {
        $this->csrf->check();
        if ($this->validPostArgument($request)) {
            try {
                $this->bot_factory->save(
                        $request->get('bot_name'),
                        $request->get('webhook_url'),
                        $request->get('avatar_url'),
                        $request->get('channels_names')
                );
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_botmattermost', 'alert_success_add_bot'));
            } catch (CannotCreateBotException $e) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
            } catch (BotAlreadyExistException $e) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->displayIndex();
    }

    private function deleteBot(HTTPRequest $request)
    {
        $this->csrf->check();
        $id = $request->get('bot_id');
        if ($this->validBotId($id)) {
            try {
                $this->bot_factory->deleteBotById($id);
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_botmattermost','alert_success_delete_bot'));
            } catch (CannotDeleteBotException $e) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->displayIndex();
    }

    private function editBot(HTTPRequest $request)
    {
        $this->csrf->check();
        $id = $request->get('bot_id');
        if ($this->validPostArgument($request) && $this->validBotId($id)) {
            try {
                $this->bot_factory->update(
                    $request->get('bot_name'),
                    $request->get('webhook_url'),
                    $request->get('avatar_url'),
                    $request->get('channels_names'),
                    $id
                );
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_botmattermost', 'alert_success_edit_bot'));
            } catch (CannotUpdateBotException $e) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->displayIndex();
    }

    private function validPostArgument(HTTPRequest $request)
    {
        if (! $request->existAndNonEmpty('bot_name') || ! $request->existAndNonEmpty('webhook_url')) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost', 'alert_error_empty_input'));
            return false;
        }

        return (
            $this->validUrl($request->get('webhook_url')) &&
            $this->validOptionnalUrl($request->get('avatar_url'))
        );
    }

    private function validOptionnalUrl($url) {
        if (! $url) {
            return true;
        }

        return $this->validUrl($url);
    }

    private function validUrl($url)
    {
        $valid_url = new Valid_HTTPURI();
        if ($valid_url->validate($url)) {
            return true;
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost', 'alert_error_invalid_url'));
            return false;
        }
    }

    private function validBotId($id)
    {

        if ($this->bot_factory->getBotById($id)) {
            return true;
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost', 'alert_error_invalid_id'));
            return false;
        }
    }

    private function redirectToAdminSectionWithErrorFeedback(Exception $e)
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
        $GLOBALS['Response']->redirect('/admin/');
    }
}
