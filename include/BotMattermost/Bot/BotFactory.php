<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
namespace Tuleap\BotMattermost\Bot;

use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\CannotDeleteBotException;
use Tuleap\BotMattermost\Exception\CannotUpdateBotException;

class BotFactory
{
    private $dao;

    public function __construct(BotDao $bot_dao)
    {
        $this->dao = $bot_dao;
    }

    /**
     * @return Bot
     */
    public function save(
        $bot_name,
        $bot_webhook_url,
        $bot_avatar_url
    ) {
        if (! $this->doesBotAlreadyExist($bot_name, $bot_webhook_url)) {
            $id = $this->dao->addBot(
                trim($bot_name),
                trim($bot_webhook_url),
                trim($bot_avatar_url)
            );
            if (! $id) {
                throw new CannotCreateBotException();
            }
        } else {
            throw new BotAlreadyExistException();
        }

        return new Bot(
            $id,
            $bot_name,
            $bot_webhook_url,
            $bot_avatar_url,
            null
        );
    }

    public function update(
        $bot_name,
        $bot_webhook_url,
        $bot_avatar_url,
        $bot_id
    ) {
        if (
            ! $this->dao->updateBot(
                trim($bot_name),
                trim($bot_webhook_url),
                trim($bot_avatar_url),
                $bot_id
            )
        ) {
            throw new CannotUpdateBotException();
        }
    }

    public function deleteBotById($id)
    {
        if (! $this->dao->deleteBot($id)) {
            throw new CannotDeleteBotException();
        }
    }

    /**
     * @return Bot[]
     */
    public function getSystemBots(): array
    {
        $dar = $this->dao->searchSystemBots();
        if ($dar === false) {
            throw new BotNotFoundException();
        }
        return $this->buildBotsFromDAR($dar);
    }

    /**
     * @return Bot[]
     */
    public function getProjectBots(int $project_id): array
    {
        $dar = $this->dao->searchProjectBots($project_id);
        if ($dar === false) {
            throw new BotNotFoundException();
        }
        return $this->buildBotsFromDAR($dar);
    }

    public function doesBotAlreadyExist($name, $webhook_url)
    {
        return $this->dao->searchBotByNameAndByWebhookUrl($name, $webhook_url);
    }

    public function getBotById($bot_id): Bot
    {
        $row = $this->dao->searchBotById($bot_id);
        if ($row === null || $row === false) {
            throw new BotNotFoundException();
        }

        return new Bot(
            $row['id'],
            $row['name'],
            $row['webhook_url'],
            $row['avatar_url'],
            $row['project_id']
        );
    }

    /**
     * @return Bot[]
     */
    private function buildBotsFromDAR(array $dar): array
    {
        $bots = [];
        foreach ($dar as $row) {
            $bots[] = new Bot(
                $row['id'],
                $row['name'],
                $row['webhook_url'],
                $row['avatar_url'],
                $row['project_id']
            );
        }

        return $bots;
    }
}
