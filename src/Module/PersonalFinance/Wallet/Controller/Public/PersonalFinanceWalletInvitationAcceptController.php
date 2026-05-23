<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Controller\Public;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Manager\PersonalFinanceWalletInvitationManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletInvitationRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Public-facing landing for a wallet invitation token. The user lands
 * here from the email CTA. The firewall guarantees the user is logged
 * in (the `IsGranted('ROLE_USER')` redirects to login + returns here
 * after successful auth). No `personal_finance.wallets.use` privilege
 * required — the token itself is the authorisation.
 */
#[Route('/personal-finance/wallet-invitations/{token}', name: 'personal_finance_wallet_invitation', requirements: ['token' => '[a-f0-9]{64}'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PersonalFinanceWalletInvitationAcceptController extends AbstractController
{
    public function __construct(
        private readonly PersonalFinanceWalletInvitationRepository $invitationRepository,
        private readonly PersonalFinanceWalletInvitationManagerInterface $invitationManager,
    ) {}

    #[Route('', name: '_accept_show', methods: [HttpMethodEnum::Get->value])]
    public function show(string $token): Response
    {
        $invitation = $this->invitationRepository->findOneByToken($token);
        $status = $this->resolveStatus($invitation);

        return $this->render('@PersonalFinance/public/wallet_invitation_accept.html.twig', [
            'invitation' => $invitation,
            'status' => $status,
        ]);
    }

    #[Route('/accept', name: '_accept', methods: [HttpMethodEnum::Post->value])]
    public function accept(string $token): RedirectResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $member = $this->invitationManager->accept($token, $user);
        if (!$member instanceof PersonalFinanceWalletMemberInterface) {
            $this->addFlash('error', 'personal_finance.wallets.errors.invitation_token_invalid');

            return $this->redirectToRoute('personal_finance_wallet_invitation_accept_show', ['token' => $token]);
        }

        $this->addFlash('success', 'personal_finance.wallets.invitations.accepted_toast');

        return $this->redirectToRoute('backend_personal_finance_wallets');
    }

    #[Route('/decline', name: '_decline', methods: [HttpMethodEnum::Post->value])]
    public function decline(string $token): RedirectResponse
    {
        $ok = $this->invitationManager->decline($token);
        if (!$ok) {
            $this->addFlash('error', 'personal_finance.wallets.errors.invitation_token_invalid');
        } else {
            $this->addFlash('success', 'personal_finance.wallets.invitations.declined_toast');
        }

        return $this->redirectToRoute('personal_finance_wallet_invitation_accept_show', ['token' => $token]);
    }

    private function resolveStatus(?PersonalFinanceWalletInvitationInterface $invitation): string
    {
        if (!$invitation instanceof PersonalFinanceWalletInvitationInterface) {
            return 'invalid';
        }

        if ($invitation->isAccepted()) {
            return 'accepted';
        }

        if ($invitation->isDeclined()) {
            return 'declined';
        }

        if ($invitation->isExpired()) {
            return 'expired';
        }

        return 'pending';
    }
}
