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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202209081615_increase_search_content_to_mediumtext extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Increase the size of the content column in the table plugin_fts_db_search to MEDIUMTEXT';
    }

    public function up(): void
    {
        $this->api->dbh->exec('ALTER TABLE plugin_fts_db_search MODIFY content MEDIUMTEXT NOT NULL');
    }
}
