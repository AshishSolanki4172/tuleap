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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\Account\Register\InvitationToEmail;
use Tuleap\User\Account\Register\RegisterFormContext;

final class AccountCreationFeedbackTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AccountCreationFeedback $account_creation_feedback;
    private LoggerInterface|MockObject $logger;
    private InvitationDao|MockObject $dao;
    private MockObject|AccountCreationFeedbackEmailNotifier $email_notifier;


    protected function setUp(): void
    {
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->dao            = $this->createMock(InvitationDao::class);
        $this->email_notifier = $this->createMock(AccountCreationFeedbackEmailNotifier::class);
    }

    public function testItUpdatesInvitationsWithJustCreatedUser(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->expects(self::once())
            ->method('saveJustCreatedUserThanksToInvitation')
            ->with('doe@example.com', 104, null);

        $this->dao
            ->expects(self::once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([]);

        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            RetrieveUserByIdStub::withNoUser(),
            $this->email_notifier,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItUpdatesInvitationsWithJustCreatedUserByInvitation(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->expects(self::once())
            ->method('saveJustCreatedUserThanksToInvitation')
            ->with('doe@example.com', 104, 1);

        $this->dao
            ->expects(self::once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([]);

        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            RetrieveUserByIdStub::withNoUser(),
            $this->email_notifier,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated(
            $new_user,
            RegisterFormContext::forAnonymous(
                true,
                InvitationToEmail::fromInvitation(
                    InvitationTestBuilder::aSentInvitation(1)
                        ->from(102)
                        ->to('doe@example.com')
                        ->build(),
                    new ConcealedString('secret')
                )
            )
        );
    }

    public function testItNotifiesNobodyIfUserWasNotInvited(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects(self::once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([]);

        $this->email_notifier
            ->expects(self::never())
            ->method('send');

        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            RetrieveUserByIdStub::withNoUser(),
            $this->email_notifier,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItNotifiesEveryPeopleWhoInvitedTheUser(): void
    {
        $from_user = UserTestBuilder::aUser()
            ->withId(103)
            ->withStatus('A')
            ->build();

        $from_another_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withStatus('A')
            ->build();

        $new_user = UserTestBuilder::aUser()
            ->withId(105)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects(self::once())
            ->method('searchByCreatedUserId')
            ->with(105)
            ->willReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                    [
                        'from_user_id' => 104,
                    ],
                ]
            );

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [$from_user, $new_user],
                [$from_another_user, $new_user],
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            RetrieveUserByIdStub::withUsers($new_user, $from_user, $from_another_user),
            $this->email_notifier,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItIgnoresUsersThatCannotBeFoundButLogsAnError(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects(self::once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                ]
            );

        $this->email_notifier
            ->expects(self::never())
            ->method('send');

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with("Invitation was referencing an unknown user #103");

        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            RetrieveUserByIdStub::withNoUser(),
            $this->email_notifier,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItIgnoresUsersThatAreNotAliveButLogsAWarning(): void
    {
        $from_user = UserTestBuilder::aUser()
            ->withId(103)
            ->withStatus('D')
            ->build();

        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects(self::once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                ]
            );

        $this->email_notifier
            ->expects(self::never())
            ->method('send');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with("Cannot send invitation feedback to inactive user #103");

        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            RetrieveUserByIdStub::withUsers($from_user),
            $this->email_notifier,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItLogsAnErrorIfEmailCannotBeSent(): void
    {
        $from_user = UserTestBuilder::aUser()
            ->withId(103)
            ->withStatus('A')
            ->build();

        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects(self::once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                ]
            );

        $this->email_notifier
            ->expects(self::once())
            ->method('send')
            ->willReturn(false);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with("Unable to send invitation feedback to user #103 after registration of user #104");

        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            RetrieveUserByIdStub::withUsers($from_user),
            $this->email_notifier,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }
}
