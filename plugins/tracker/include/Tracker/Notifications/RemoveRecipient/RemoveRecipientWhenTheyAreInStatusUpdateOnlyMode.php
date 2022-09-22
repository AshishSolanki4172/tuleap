<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\RemoveRecipient;

use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Notifications\GetUserFromRecipient;
use Tuleap\Tracker\Notifications\RecipientRemovalStrategy;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChange;

final class RemoveRecipientWhenTheyAreInStatusUpdateOnlyMode implements RecipientRemovalStrategy
{
    public function __construct(
        private GetUserFromRecipient $get_user_from_recipient,
        private UserNotificationOnlyStatusChange $user_status_change_only,
        private ArtifactStatusChangeDetector $artifact_status_change_detector,
    ) {
    }

    public function removeRecipient(
        LoggerInterface $logger,
        Tracker_Artifact_Changeset $changeset,
        array $recipients,
        bool $is_update,
    ): array {
        if ($this->artifact_status_change_detector->hasChanged($changeset)) {
            $logger->debug(self::class . ' artifact status has changed, skipped');
            return $recipients;
        }

        foreach ($recipients as $recipient => $is_notification_enabled) {
            $user = $this->get_user_from_recipient->getUserFromRecipientName($recipient);
            if (! $user) {
                continue;
            }

            if ($this->user_status_change_only->doesUserIdHaveSubscribeOnlyForStatusChangeNotification((int) $user->getId(), $changeset->getTracker()->getId())) {
                $logger->debug(self::class . ' ' . $recipient . ' want notification only for status change, removed');
                unset($recipients[$recipient]);
            }
        }
        return $recipients;
    }
}
