<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

final class JiraEpicRetrieverFromAPITest extends TestCase
{
    public function testItCallsTheEpicsURL(): void
    {
        $client = new class implements JiraClient {
            public $called = false;
            public function getUrl(string $url): ?array
            {
                $this->called = true;
                assertEquals('/rest/agile/latest/board/1/epic?startAt=0', $url);
                return [
                    'isLast' => true,
                    'values' => [],
                ];
            }
        };

        $epic_retriever = new JiraEpicRetrieverFromAPI($client, new NullLogger());
        $epic_retriever->getEpics(JiraBoard::buildFakeBoard());

        assertTrue($client->called);
    }

    public function testIfBuildEpics(): void
    {
        $client = new class implements JiraClient {
            public function getUrl(string $url): ?array
            {
                return [
                    'isLast' => true,
                    'values' => [
                        [
                            "id"      => 10143,
                            "key"     => "SP-36",
                            "self"    => "https://example.com/rest/agile/1.0/epic/10143",
                            "name"    => "Big Epic",
                            "summary" => "Some Epic",
                            "color"   => [
                                "key" => "color_11",
                            ],
                            "done"    => false,
                        ],
                    ],
                ];
            }
        };

        $epic_retriever = new JiraEpicRetrieverFromAPI($client, new NullLogger());
        $epics          = $epic_retriever->getEpics(JiraBoard::buildFakeBoard());

        assertCount(1, $epics);
        assertEquals(10143, $epics[0]->id);
        assertEquals("https://example.com/rest/agile/1.0/epic/10143", $epics[0]->url);
        assertEquals("SP-36", $epics[0]->key);
    }
}
