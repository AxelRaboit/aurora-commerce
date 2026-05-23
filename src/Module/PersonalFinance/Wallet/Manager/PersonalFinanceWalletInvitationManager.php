<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletInvitationInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletMemberInputFactoryInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitation;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\PersonalFinance\Wallet\Notification\PersonalFinanceWalletInvitationNotificationService;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletInvitationRepository;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletMemberRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletInvitationManagerInterface::class)]
class PersonalFinanceWalletInvitationManager implements PersonalFinanceWalletInvitationManagerInterface
{
    public const int EXPIRY_DAYS = 14;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceWalletInvitationRepository $invitationRepository,
        protected readonly PersonalFinanceWalletMemberRepository $memberRepository,
        protected readonly PersonalFinanceWalletMemberManagerInterface $memberManager,
        protected readonly PersonalFinanceWalletMemberInputFactoryInterface $memberInputFactory,
        protected readonly PersonalFinanceWalletInvitationNotificationService $notification,
    ) {}

    public function send(
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceWalletInvitationInputInterface $input,
        CoreUserInterface $invitedBy,
    ): PersonalFinanceWalletInvitationInterface {
        $role = $input->getRole();
        if (!$role instanceof PersonalFinanceWalletRoleEnum || !in_array($role, PersonalFinanceWalletRoleEnum::invitable(), true)) {
            throw new DomainException('Owner role cannot be assigned via invitation. Use transferOwnership instead.');
        }

        $existing = $this->invitationRepository->findOneByWalletAndEmail($wallet, $input->getEmail());
        if ($existing instanceof PersonalFinanceWalletInvitationInterface && $existing->isPending()) {
            throw new DomainException('A pending invitation already exists for this email on this wallet.');
        }

        $invitation = $this->createInvitation();
        $this->applyInput($invitation, $input);
        $invitation->setWallet($wallet);
        $invitation->setInvitedBy($invitedBy);
        $invitation->setToken($this->generateToken());
        $invitation->setExpiresAt($this->computeExpiresAt());

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        $this->auditCreated($invitation);
        $this->notification->notifyInvited($invitation);

        return $invitation;
    }

    protected function applyInput(PersonalFinanceWalletInvitationInterface $invitation, PersonalFinanceWalletInvitationInputInterface $input): void
    {
        $invitation->setEmail($input->getEmail());
        $invitation->setRole($input->getRole());
    }

    public function accept(string $token, CoreUserInterface $accepter): ?PersonalFinanceWalletMemberInterface
    {
        $invitation = $this->invitationRepository->findOneByToken($token);
        if (!$invitation instanceof PersonalFinanceWalletInvitationInterface || !$invitation->isPending()) {
            return null;
        }

        $alreadyMember = $this->memberRepository->findOneByWalletAndUser($invitation->getWallet(), $accepter);
        if ($alreadyMember instanceof PersonalFinanceWalletMemberInterface) {
            $invitation->setAcceptedAt(new DateTimeImmutable());
            $this->entityManager->flush();

            return $alreadyMember;
        }

        $member = $this->memberManager->create(
            $invitation->getWallet(),
            $accepter,
            $this->memberInputFactory->fromRole($invitation->getRole()),
        );

        $invitation->setAcceptedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->auditAccepted($invitation);

        return $member;
    }

    public function decline(string $token): bool
    {
        $invitation = $this->invitationRepository->findOneByToken($token);
        if (!$invitation instanceof PersonalFinanceWalletInvitationInterface || !$invitation->isPending()) {
            return false;
        }

        $invitation->setDeclinedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->auditDeclined($invitation);

        return true;
    }

    public function revoke(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        if (!$invitation->isPending()) {
            throw new DomainException('Cannot revoke an invitation that is not pending.');
        }

        $invitation->setDeclinedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->auditRevoked($invitation);
    }

    public function resend(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        if ($invitation->isAccepted() || $invitation->isDeclined()) {
            throw new DomainException('Cannot resend an invitation that was already accepted or declined.');
        }

        $invitation->setToken($this->generateToken());
        $invitation->setExpiresAt($this->computeExpiresAt());

        $this->entityManager->flush();

        $this->auditResent($invitation);
        $this->notification->notifyInvited($invitation);
    }

    protected function createInvitation(): PersonalFinanceWalletInvitationInterface
    {
        return new PersonalFinanceWalletInvitation();
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    protected function computeExpiresAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('+'.self::EXPIRY_DAYS.' days');
    }

    protected function auditCreated(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_invitation.sent', 'PersonalFinanceWalletInvitation', $invitation->getId(), $this->auditPayload($invitation));
    }

    protected function auditAccepted(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_invitation.accepted', 'PersonalFinanceWalletInvitation', $invitation->getId(), $this->auditPayload($invitation));
    }

    protected function auditDeclined(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_invitation.declined', 'PersonalFinanceWalletInvitation', $invitation->getId(), $this->auditPayload($invitation));
    }

    protected function auditRevoked(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_invitation.revoked', 'PersonalFinanceWalletInvitation', $invitation->getId(), $this->auditPayload($invitation));
    }

    protected function auditResent(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_invitation.resent', 'PersonalFinanceWalletInvitation', $invitation->getId(), $this->auditPayload($invitation));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceWalletInvitationInterface $invitation): array
    {
        return [
            'walletId' => $invitation->getWallet()->getId(),
            'email' => $invitation->getEmail(),
            'role' => $invitation->getRole()->value,
        ];
    }
}
