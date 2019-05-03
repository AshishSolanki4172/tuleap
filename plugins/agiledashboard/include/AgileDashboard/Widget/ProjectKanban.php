<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Widget;

use AgileDashboard_KanbanFactory;
use AgileDashboard_PermissionsManager;
use HTTPRequest;
use TrackerFactory;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportBuilder;
use Tuleap\Dashboard\Project\ProjectDashboardController;

class ProjectKanban extends Kanban
{
    public const NAME = 'plugin_agiledashboard_projects_kanban';

    public function __construct(
        WidgetKanbanCreator $widget_kanban_creator,
        WidgetKanbanRetriever $widget_kanban_retriever,
        WidgetKanbanDeletor $widget_kanban_deletor,
        AgileDashboard_KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory,
        AgileDashboard_PermissionsManager $permissions_manager,
        WidgetKanbanConfigRetriever $widget_kanban_config_retriever,
        WidgetKanbanConfigUpdater $widget_kanban_config_updater,
        \Tracker_ReportFactory $tracker_report_factory
    ) {
        parent::__construct(
            self::NAME,
            HTTPRequest::instance()->get('group_id'),
            ProjectDashboardController::LEGACY_DASHBOARD_TYPE,
            $widget_kanban_creator,
            $widget_kanban_retriever,
            $widget_kanban_deletor,
            $kanban_factory,
            $tracker_factory,
            $permissions_manager,
            $widget_kanban_config_retriever,
            $widget_kanban_config_updater,
            $tracker_report_factory
        );
    }
}
