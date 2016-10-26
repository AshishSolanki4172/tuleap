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

namespace Tuleap\BotMattermostAgileDashboard\BotAgileDashboard;

use DataAccessObject;

class BotAgileDashboardDao extends DataAccessObject
{

    public function searchTimeAndDuration($bot_id, $project_id)
    {
        $bot_id     = $this->da->escapeInt($bot_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT start_time, duration
                FROM plugin_botmattermost_agiledashboard
                WHERE bot_id = $bot_id
                    AND project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function updateBotsAgileDashboard(
        array $bots_ids,
        $project_id,
        $start_time,
        $duration
    ) {
        $this->getDa()->startTransaction();

        if (! $this->deleteBotsForProject($project_id)) {
            $this->getDa()->rollback();
            return false;
        }

        if (count($bots_ids) > 0) {
            if (! $this->addBotsAgileDashboard(
                $bots_ids,
                $project_id,
                $start_time,
                $duration
            )) {
                $this->getDa()->rollback();
                return false;
            }
        }

        return $this->getDa()->commit();
    }

    private function addBotsAgileDashboard(
        array $bots_ids,
        $project_id,
        $start_time,
        $duration
    ) {
        $sql = "INSERT INTO plugin_botmattermost_agiledashboard(bot_id, project_id, start_time, duration)
                VALUES ";

        foreach($bots_ids as $bot_id) {
            $sql .= $this->addBotAgileDashboardSql($bot_id, $project_id, $start_time, $duration).',';
        }

        return $this->update(trim($sql, ','));
    }

    private function addBotAgileDashboardSql($bot_id, $project_id, $start_time, $duration)
    {
        $bot_id     = $this->da->escapeInt($bot_id);
        $project_id = $this->da->escapeInt($project_id);
        $start_time = $this->da->quoteSmart($start_time);
        $duration   = $this->da->quoteSmart($duration);

        return "($bot_id, $project_id, $start_time, $duration)";
    }

    public function deleteBotsForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE FROM plugin_botmattermost_agiledashboard
                WHERE project_id = $project_id";

        return $this->update($sql);
    }
}
