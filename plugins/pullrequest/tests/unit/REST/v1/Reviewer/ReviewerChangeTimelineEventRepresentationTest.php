<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Reviewer;

use Tuleap\GlobalLanguageMock;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChange;
use Tuleap\Test\Builders\UserTestBuilder;

final class ReviewerChangeTimelineEventRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testCanBuildRepresentationFromAReviewerChange(): void
    {
        $reviewer_change = new ReviewerChange(
            new \DateTimeImmutable('@10'),
            $this->buildUser(102),
            [$this->buildUser(103)],
            [$this->buildUser(104)]
        );

        $representation = ReviewerChangeTimelineEventRepresentation::fromReviewerChange($reviewer_change);

        $this->assertEquals('reviewer-change', $representation->type);
        $this->assertEquals('1970-01-01T01:00:10+01:00', $representation->post_date);
        $this->assertEquals(102, $representation->user->id);
        $this->assertCount(1, $representation->added_reviewers);
        $this->assertEquals(103, $representation->added_reviewers[0]->id);
        $this->assertCount(1, $representation->removed_reviewers);
        $this->assertEquals(104, $representation->removed_reviewers[0]->id);
    }

    private function buildUser(int $user_id): \PFUser
    {
        return UserTestBuilder::aUser()->withId($user_id)->withUserName("user")->withRealName("real")->build();
    }
}
