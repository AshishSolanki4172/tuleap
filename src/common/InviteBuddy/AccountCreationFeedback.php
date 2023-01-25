<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Psr\Log\LoggerInterface;
use Tuleap\User\Account\Register\RegisterFormContext;
use Tuleap\User\RetrieveUserById;

class AccountCreationFeedback implements InvitationSuccessFeedback
{
    public function __construct(
        private InvitationDao $dao,
        private RetrieveUserById $user_manager,
        private AccountCreationFeedbackEmailNotifier $email_notifier,
        private LoggerInterface $logger,
    ) {
    }

    public function accountHasJustBeenCreated(\PFUser $just_created_user, RegisterFormContext $context): void
    {
        $this->dao->saveJustCreatedUserThanksToInvitation(
            (string) $just_created_user->getEmail(),
            (int) $just_created_user->getId(),
            $context->invitation_to_email ? $context->invitation_to_email->id : null
        );

        foreach ($this->dao->searchByCreatedUserId((int) $just_created_user->getId()) as $row) {
            $from_user = $this->user_manager->getUserById($row['from_user_id']);
            if (! $from_user) {
                $this->logger->error("Invitation was referencing an unknown user #" . $row['from_user_id']);
                continue;
            }
            if (! $from_user->isAlive()) {
                $this->logger->warning("Cannot send invitation feedback to inactive user #" . $row['from_user_id']);
                continue;
            }

            if (! $this->email_notifier->send($from_user, $just_created_user)) {
                $this->logger->error(
                    "Unable to send invitation feedback to user #{$from_user->getId()} after registration of user #{$just_created_user->getId()}"
                );
            }
        }
    }
}
