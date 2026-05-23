<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Notification;

use Aurora\Core\Mail\Service\MailService;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Thin shell around MailService for wallet-sharing invitations. Lives
 * in `Notification/` rather than `Service/` because its only job is
 * external side-effects (email send) — the invitation lifecycle
 * itself stays in the Manager. `readonly` (not final) so a client can
 * extend it to swap the template, the from address, or add a CC.
 *
 * The accept URL targets the public-facing route
 * `personal_finance_wallet_invitation_accept`. If that route is not
 * registered (e.g. tests, or the public sub-feature disabled), the
 * email content gracefully degrades to the in-app modal URL.
 */
readonly class PersonalFinanceWalletInvitationNotificationService
{
    public function __construct(
        protected MailService $mail,
        protected UrlGeneratorInterface $urlGenerator,
    ) {}

    public function notifyInvited(PersonalFinanceWalletInvitationInterface $invitation): void
    {
        $wallet = $invitation->getWallet();
        $inviter = $invitation->getInvitedBy();
        $acceptUrl = $this->acceptUrl($invitation->getToken());

        $this->mail->send(
            $invitation->getEmail(),
            'personal_finance.mail.invitation.subject',
            '@PersonalFinance/email/wallet_invitation.html.twig',
            [
                'invitation' => $invitation,
                'walletName' => $wallet->getName(),
                'inviterName' => $inviter->getName(),
                'role' => $invitation->getRole(),
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $invitation->getExpiresAt(),
            ],
            subjectParams: ['{walletName}' => $wallet->getName()],
        );
    }

    protected function acceptUrl(string $token): string
    {
        try {
            return $this->urlGenerator->generate(
                'personal_finance_wallet_invitation_accept_show',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        } catch (\Exception) {
            // Public route not registered — degrade to the backend index.
            return $this->urlGenerator->generate(
                'backend_personal_finance_wallets',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        }
    }
}
