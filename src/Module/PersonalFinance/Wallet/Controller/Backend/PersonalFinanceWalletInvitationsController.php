<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletInvitationInputFactoryInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Manager\PersonalFinanceWalletInvitationManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletInvitationRepository;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletInvitationSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletMemberSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.wallets.use')]
final class PersonalFinanceWalletInvitationsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PersonalFinanceWalletInvitationRepository $invitationRepository,
        private readonly PersonalFinanceWalletInvitationManagerInterface $invitationManager,
        private readonly PersonalFinanceWalletInvitationInputFactoryInterface $invitationInputFactory,
        private readonly PersonalFinanceWalletInvitationSerializerInterface $invitationSerializer,
        private readonly PersonalFinanceWalletMemberSerializerInterface $memberSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/wallets/{walletId}/invitations/send', name: '_wallets_invitations_send', methods: [HttpMethodEnum::Post->value])]
    public function send(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::MANAGE_MEMBERS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->invitationInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $invitation = $this->invitationManager->send($wallet, $input, $user);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['email' => [$domainException->getMessage()]]);
        }

        return $this->jsonSuccess([
            'invitation' => $this->invitationSerializer->serialize($invitation),
            'token' => $invitation->getToken(),
        ]);
    }

    #[Route('/wallets/{walletId}/invitations/{invitationId}/revoke', name: '_wallets_invitations_revoke', methods: [HttpMethodEnum::Post->value])]
    public function revoke(int $walletId, int $invitationId): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::MANAGE_MEMBERS, $wallet);

        $invitation = $this->invitationRepository->find($invitationId);
        if (!$invitation instanceof PersonalFinanceWalletInvitationInterface
            || $invitation->getWallet()->getId() !== $wallet->getId()) {
            return $this->jsonNotFound();
        }

        try {
            $this->invitationManager->revoke($invitation);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['invitation' => [$domainException->getMessage()]]);
        }

        return $this->jsonSuccess();
    }

    #[Route('/wallets/{walletId}/invitations/{invitationId}/resend', name: '_wallets_invitations_resend', methods: [HttpMethodEnum::Post->value])]
    public function resend(int $walletId, int $invitationId): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::MANAGE_MEMBERS, $wallet);

        $invitation = $this->invitationRepository->find($invitationId);
        if (!$invitation instanceof PersonalFinanceWalletInvitationInterface
            || $invitation->getWallet()->getId() !== $wallet->getId()) {
            return $this->jsonNotFound();
        }

        try {
            $this->invitationManager->resend($invitation);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['invitation' => [$domainException->getMessage()]]);
        }

        return $this->jsonSuccess([
            'invitation' => $this->invitationSerializer->serialize($invitation),
            'token' => $invitation->getToken(),
        ]);
    }

    #[Route('/wallet-invitations/{token}/accept', name: '_wallet_invitations_accept', methods: [HttpMethodEnum::Post->value])]
    public function accept(string $token): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $member = $this->invitationManager->accept($token, $user);
        if (!$member instanceof PersonalFinanceWalletMemberInterface) {
            return $this->jsonInvalidInput(['token' => ['Invalid, expired or already-used invitation token.']]);
        }

        return $this->jsonSuccess(['member' => $this->memberSerializer->serialize($member)]);
    }

    #[Route('/wallet-invitations/{token}/decline', name: '_wallet_invitations_decline', methods: [HttpMethodEnum::Post->value])]
    public function decline(string $token): JsonResponse
    {
        if (!$this->invitationManager->decline($token)) {
            return $this->jsonInvalidInput(['token' => ['Invalid or already-used invitation token.']]);
        }

        return $this->jsonSuccess();
    }
}
