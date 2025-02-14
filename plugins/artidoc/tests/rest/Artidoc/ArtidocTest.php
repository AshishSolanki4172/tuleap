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

require_once __DIR__ . '/../../../../docman/vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

final class ArtidocTest extends DocmanTestExecutionHelper
{
    private string $now = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->now = (string) microtime();
    }

    /**
     * @depends testGetRootId
     */
    public function testArtidocCreation(int $root_id): int
    {
        $post_response_json = $this->createArtidoc($root_id, 'Artidoc F1 ' . $this->now);

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $post_response_json['uri'])
        );
        self::assertSame(200, $item_response->getStatusCode());
        $item_response_json = json_decode($item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('artidoc', $item_response_json['type']);
        self::assertSame('Artidoc F1 ' . $this->now, $item_response_json['title']);

        return $post_response_json['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testArtidocMove(int $root_id): void
    {
        $folder_source_id      = $this->createFolder($root_id, 'Folder source to contain item F2 to move. ' . $this->now)['id'];
        $folder_destination_id = $this->createFolder($root_id, 'Folder target to move item F2 into. ' . $this->now)['id'];

        $item_id = $this->createArtidoc($folder_source_id, 'Artidoc F2 ' . $this->now)['id'];

        self::assertCount(1, $this->getFolderContent($folder_source_id));
        self::assertCount(0, $this->getFolderContent($folder_destination_id));

        $move_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'artidoc/' . $item_id)
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'move' => [
                                    'destination_folder_id' => $folder_destination_id,
                                ],
                            ]
                        )
                    )
                )
        );
        self::assertSame(200, $move_item_response->getStatusCode());

        self::assertCount(0, $this->getFolderContent($folder_source_id));
        $folder_destination_content = $this->getFolderContent($folder_destination_id);
        self::assertCount(1, $folder_destination_content);
        self::assertSame($item_id, $folder_destination_content[0]['id']);
    }

    private function createArtidoc(int $parent_id, string $title): array
    {
        $post_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $parent_id . '/others')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'title' => $title,
                                'type'  => 'artidoc',
                            ]
                        )
                    )
                )
        );
        self::assertSame(201, $post_item_response->getStatusCode());

        $post_response_json = json_decode($post_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($post_response_json['file_properties']);

        return $post_response_json;
    }

    private function createFolder(int $parent_id, string $title): array
    {
        $post_folder_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory
                ->createRequest('POST', 'docman_folders/' . $parent_id . '/folders')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'title' => $title,
                            ]
                        ),
                    )
                )
        );
        self::assertSame(201, $post_folder_response->getStatusCode());

        return json_decode($post_folder_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function getFolderContent(int $id): array
    {
        return json_decode(
            $this->getResponseByName(
                DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
                $this->request_factory->createRequest('GET', 'docman_items/' . $id . '/docman_items'),
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostOtherTypeDocumentDeniedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title' => 'Artidoc F2 ' . $this->now,
                'type'  => 'artidoc',
            ]
        );

        $response1 = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/others')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(403, $response1->getStatusCode());
    }

    /**
     * @depends testArtidocCreation
     */
    public function testOptionsDocument(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/' . $id));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    /**
     * @depends testArtidocCreation
     */
    public function testOptionsSections(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/' . $id . '/sections'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'GET', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }

    /**
     * @depends testGetRootId
     */
    public function testPUTArtidocSections(int $root_id): void
    {
        $artidoc_id   = $this->createArtidoc($root_id, 'Artidoc F3 ' . $this->now)['id'];
        $section_1_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        self::assertCount(0, $this->getArtidocSections($artidoc_id));

        $this->addSectionsToArtidoc($artidoc_id, $section_1_id, $section_2_id);

        $document_content = $this->getArtidocSections($artidoc_id);

        self::assertCount(2, $document_content);
        self::assertSame($section_1_id, $document_content[0]['artifact']['id']);
        self::assertSame($section_2_id, $document_content[1]['artifact']['id']);
    }

    /**
     * @depends testGetRootId
     */
    public function testArtidocCopy(int $root_id): void
    {
        $artidoc_id   = $this->createArtidoc($root_id, 'Artidoc F6 ' . $this->now)['id'];
        $section_1_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        $this->addSectionsToArtidoc($artidoc_id, $section_1_id, $section_2_id);

        $folder_id = $this->createFolder($root_id, 'Folder to copy item F6 into. ' . $this->now)['id'];
        self::assertCount(0, $this->getFolderContent($folder_id));

        $copy_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $folder_id . '/others')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'copy' => [
                                    'item_id'  => $artidoc_id,
                                ],
                            ]
                        )
                    )
                )
        );
        self::assertSame(201, $copy_item_response->getStatusCode());

        $copy_response_json = json_decode($copy_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($copy_response_json['file_properties']);
        $new_artidoc_id = $copy_response_json['id'];

        self::assertNotSame($artidoc_id, $new_artidoc_id);

        $document_content = $this->getArtidocSections($new_artidoc_id);

        self::assertCount(2, $document_content);
        self::assertSame($section_1_id, $document_content[0]['artifact']['id']);
        self::assertSame($section_2_id, $document_content[1]['artifact']['id']);
    }

    private function addSectionsToArtidoc(int $artidoc_id, int ...$section_ids): void
    {
        $put_response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artidoc/' . $artidoc_id . '/sections')->withBody(
                $this->stream_factory->createStream(json_encode([
                    'sections' => array_map(static fn($id) => ['artifact' => ['id' => $id]], $section_ids),
                ], JSON_THROW_ON_ERROR))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        self::assertSame(200, $put_response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testDELETEArtidoc(int $root_id): void
    {
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc F5 ' . $this->now)['id'];

        $delete_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_other_type_documents/' . $artidoc_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $delete_response->getStatusCode());

        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $artidoc_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(404, $get_response->getStatusCode());
    }

    private function createRequirementArtifact(string $title, string $description): int
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'artifacts')->withBody(
                $this->stream_factory->createStream(json_encode([
                    'tracker' => ['id' => $this->requirements_tracker_id],
                    'values_by_field' => [
                        'title' => ['value' => $title],
                        'description' => ['value' => $description],
                    ],
                ], JSON_THROW_ON_ERROR))
            ),
        );

        $response_content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $response_content['id'];
    }

    private function getArtidocSections(int $artidoc_id): array
    {
        return json_decode(
            $this->getResponse(
                $this->request_factory->createRequest('GET', 'artidoc/' . $artidoc_id . '/sections')
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
