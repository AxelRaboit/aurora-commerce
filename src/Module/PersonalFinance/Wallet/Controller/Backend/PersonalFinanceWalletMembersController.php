<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletMemberInputFactoryInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Manager\PersonalFinanceWalletMemberManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletInvitationRepository;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletMemberRepository;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletInvitationSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletMemberSerializerInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/wallets/{walletId}/members', name: 'backend_personal_finance_wallets_members')]
#[IsGranted('personal_finance.wallets.use')]
final class PersonalFinanceWalletMembersController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PersonalFinanceWalletMemberRepository $memberRepository,
        private readonly PersonalFinanceWalletInvitationRepository $invitationRepository,
        private readonly PersonalFinanceWalletMemberManagerInterface $memberManager,
        private readonly PersonalFinanceWalletMemberInputFactoryInterface $memberInputFactory,
        private readonly PersonalFinanceWalletMemberSerializerInterface $memberSerializer,
        private readonly PersonalFinanceWalletInvitationSerializerInterface $invitationSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    /**
     * Drill-down for the Members modal — returns both the active
     * membership list and the pending invitations in a single round trip.
     */
    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function list(int $walletId): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::MANAGE_MEMBERS, $wallet);

        return $this->jsonSuccess([
            'members' => array_map($this->memberSerializer->serialize(...), $this->memberRepository->findByWallet($wallet)),
            'invitations' => array_map($this->invitationSerializer->serialize(...), $this->invitationRepository->findPendingByWallet($wallet)),
        ]);
    }

    #[Route('/{memberId}/update-role', name: '_update_role', methods: [HttpMethodEnum::Post->value])]
    public function updateRole(int $walletId, int $memberId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::MANAGE_MEMBERS, $wallet);

        $member = $this->memberRepository->find($memberId);
        if (!$member instanceof PersonalFinanceWalletMemberInterface
            || $member->getWallet()->getId() !== $wallet->getId()) {
            return $this->jsonNotFound();
        }

        $input = $this->memberInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->memberManager->update($member, $input);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['role' => [$domainException->getMessage()]]);
        }

        return $this->jsonSuccess(['member' => $this->memberSerializer->serialize($member)]);
    }

    #[Route('/{memberId}/remove', name: '_remove', methods: [HttpMethodEnum::Post->value])]
    public function remove(int $walletId, int $memberId): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::MANAGE_MEMBERS, $wallet);

        $member = $this->memberRepository->find($memberId);
        if (!$member instanceof PersonalFinanceWalletMemberInterface
            || $member->getWallet()->getId() !== $wallet->getId()) {
            return $this->jsonNotFound();
        }

        try {
            $this->memberManager->removeMember($member);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['member' => [$domainException->getMessage()]]);
        }

        return $this->jsonSuccess();
    }
}
