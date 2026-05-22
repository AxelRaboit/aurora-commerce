<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletInputFactoryInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Manager\PersonalFinanceWalletManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\View\PersonalFinanceWalletsViewBuilder;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/wallets', name: 'backend_personal_finance_wallets')]
#[IsGranted('personal_finance.wallets.use')]
final class PersonalFinanceWalletsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceWalletSerializerInterface $personalFinanceWalletSerializer,
        private readonly PersonalFinanceWalletManagerInterface $personalFinanceWalletManager,
        private readonly PersonalFinanceWalletRepository $personalFinanceWalletRepository,
        private readonly PersonalFinanceWalletInputFactoryInterface $personalFinanceWalletInputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly PersonalFinanceWalletsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render('@PersonalFinance/backend/wallets/index.html.twig', $this->viewBuilder->indexView($user));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->personalFinanceWalletInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $wallet = $this->personalFinanceWalletManager->create($user, $input);

        return $this->jsonSuccess(['wallet' => $this->personalFinanceWalletSerializer->serialize($wallet)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        $wallet = $this->personalFinanceWalletRepository->find($id);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT, $wallet);

        $input = $this->personalFinanceWalletInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->personalFinanceWalletManager->update($wallet, $input);

        return $this->jsonSuccess(['wallet' => $this->personalFinanceWalletSerializer->serialize($wallet)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $wallet = $this->personalFinanceWalletRepository->find($id);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::DELETE, $wallet);

        $this->personalFinanceWalletManager->delete($wallet);

        return $this->jsonSuccess();
    }
}
