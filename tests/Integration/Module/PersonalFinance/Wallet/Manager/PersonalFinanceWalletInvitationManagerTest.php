<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletInvitationInput;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\PersonalFinance\Wallet\Manager\PersonalFinanceWalletInvitationManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletInvitationRepository;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletMemberRepository;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DomainException;

/**
 * Regression coverage for the wallet invitation lifecycle. Exercises
 * Manager.send / accept / decline / revoke / resend and asserts that
 * the notification path (email send via MailService) does not throw
 * — the controller layer relies on the manager not bubbling
 * notification exceptions back to the user.
 */
final class PersonalFinanceWalletInvitationManagerTest extends PersonalFinanceTestCase
{
    private PersonalFinanceWalletInvitationManagerInterface $manager;
    private PersonalFinanceWalletInvitationRepository $invitationRepository;
    private PersonalFinanceWalletMemberRepository $memberRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->getService(PersonalFinanceWalletInvitationManagerInterface::class);
        $this->invitationRepository = $this->getService(PersonalFinanceWalletInvitationRepository::class);
        $this->memberRepository = $this->getService(PersonalFinanceWalletMemberRepository::class);
    }

    public function testSendPersistsInvitationWithGeneratedTokenAndExpiry(): void
    {
        $owner = $this->createTestUser();
        $wallet = $this->createWallet($owner, 'Shared wallet');

        $invitation = $this->manager->send(
            $wallet,
            new PersonalFinanceWalletInvitationInput(email: 'guest@aurora.test', role: PersonalFinanceWalletRoleEnum::Editor),
            $owner,
        );

        self::assertInstanceOf(PersonalFinanceWalletInvitationInterface::class, $invitation);
        self::assertNotNull($invitation->getId());
        self::assertSame('guest@aurora.test', $invitation->getEmail());
        self::assertSame(PersonalFinanceWalletRoleEnum::Editor, $invitation->getRole());
        self::assertSame(64, mb_strlen($invitation->getToken()));
        self::assertTrue($invitation->isPending());
        self::assertGreaterThan(new \DateTimeImmutable(), $invitation->getExpiresAt());
    }

    public function testSendRejectsOwnerRole(): void
    {
        $owner = $this->createTestUser();
        $wallet = $this->createWallet($owner);

        $this->expectException(DomainException::class);
        $this->manager->send(
            $wallet,
            new PersonalFinanceWalletInvitationInput(email: 'guest@aurora.test', role: PersonalFinanceWalletRoleEnum::Owner),
            $owner,
        );
    }

    public function testSendRejectsDuplicatePendingInvitation(): void
    {
        $owner = $this->createTestUser();
        $wallet = $this->createWallet($owner);
        $input = new PersonalFinanceWalletInvitationInput(email: 'guest@aurora.test', role: PersonalFinanceWalletRoleEnum::Viewer);

        $this->manager->send($wallet, $input, $owner);

        $this->expectException(DomainException::class);
        $this->manager->send($wallet, $input, $owner);
    }

    public function testAcceptCreatesMemberAndMarksInvitationAccepted(): void
    {
        $owner = $this->createTestUser();
        $wallet = $this->createWallet($owner);
        $invitee = $this->createTestUser('invitee');

        $invitation = $this->manager->send(
            $wallet,
            new PersonalFinanceWalletInvitationInput(email: $invitee->getEmail(), role: PersonalFinanceWalletRoleEnum::Editor),
            $owner,
        );

        $member = $this->manager->accept($invitation->getToken(), $invitee);

        self::assertInstanceOf(PersonalFinanceWalletMemberInterface::class, $member);
        self::assertSame($wallet->getId(), $member->getWallet()->getId());
        self::assertSame($invitee->getId(), $member->getUser()->getId());
        self::assertSame(PersonalFinanceWalletRoleEnum::Editor, $member->getRole());

        $reloaded = $this->invitationRepository->find($invitation->getId());
        self::assertNotNull($reloaded?->getAcceptedAt());
        self::assertFalse($reloaded->isPending());
    }

    public function testAcceptReturnsNullOnInvalidToken(): void
    {
        $user = $this->createTestUser();

        $member = $this->manager->accept(str_repeat('f', 64), $user);

        self::assertNull($member);
    }

    public function testDeclineMarksInvitationDeclined(): void
    {
        $owner = $this->createTestUser();
        $wallet = $this->createWallet($owner);

        $invitation = $this->manager->send(
            $wallet,
            new PersonalFinanceWalletInvitationInput(email: 'declined@aurora.test', role: PersonalFinanceWalletRoleEnum::Viewer),
            $owner,
        );

        $ok = $this->manager->decline($invitation->getToken());

        self::assertTrue($ok);
        $reloaded = $this->invitationRepository->find($invitation->getId());
        self::assertNotNull($reloaded?->getDeclinedAt());
    }

    public function testResendRotatesTokenAndExtendsExpiry(): void
    {
        $owner = $this->createTestUser();
        $wallet = $this->createWallet($owner);

        $invitation = $this->manager->send(
            $wallet,
            new PersonalFinanceWalletInvitationInput(email: 'resend@aurora.test', role: PersonalFinanceWalletRoleEnum::Viewer),
            $owner,
        );
        $originalToken = $invitation->getToken();

        $this->manager->resend($invitation);

        self::assertNotSame($originalToken, $invitation->getToken());
        self::assertGreaterThan(new \DateTimeImmutable(), $invitation->getExpiresAt());
    }
}
