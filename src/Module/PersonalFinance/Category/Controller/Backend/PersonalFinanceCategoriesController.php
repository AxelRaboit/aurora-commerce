<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Category\Dto\PersonalFinanceCategoryInputFactoryInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Manager\PersonalFinanceCategoryManagerInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Category\View\PersonalFinanceCategoriesViewBuilder;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.categories.use')]
final class PersonalFinanceCategoriesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceCategorySerializerInterface $categorySerializer,
        private readonly PersonalFinanceCategoryManagerInterface $categoryManager,
        private readonly PersonalFinanceCategoryRepository $categoryRepository,
        private readonly PersonalFinanceCategoryInputFactoryInterface $categoryInputFactory,
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly PersonalFinanceCategoriesViewBuilder $viewBuilder,
    ) {}

    #[Route('/categories', name: '_categories', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request, PaginationRequest $pagination): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $walletId = $request->query->getInt('walletId') ?: null;

        return $this->render(
            '@PersonalFinance/backend/categories/index.html.twig',
            $this->viewBuilder->indexView($user, $pagination, $walletId),
        );
    }

    #[Route('/categories/list', name: '_categories_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request, PaginationRequest $pagination): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $walletId = $request->query->getInt('walletId') ?: null;
        if (null === $walletId) {
            return $this->json(['success' => true, 'items' => [], 'page' => 1, 'totalPages' => 1, 'total' => 0]);
        }

        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $wallet);

        return $this->json($this->viewBuilder->buildListPayload($wallet, $pagination));
    }

    #[Route('/wallets/{walletId}/categories/create', name: '_wallets_categories_create', methods: [HttpMethodEnum::Post->value])]
    public function create(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->categoryInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $category = $this->categoryManager->create($user, $wallet, $input);

        return $this->jsonSuccess(['category' => $this->categorySerializer->serialize($category)]);
    }

    #[Route('/categories/{id}/update', name: '_categories_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $category->getWallet());

        $input = $this->categoryInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->categoryManager->update($category, $input);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['name' => [$domainException->getMessage()]]);
        }

        return $this->jsonSuccess(['category' => $this->categorySerializer->serialize($category)]);
    }

    #[Route('/categories/{id}/delete', name: '_categories_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $category->getWallet());

        try {
            $this->categoryManager->delete($category);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['category' => [$domainException->getMessage()]]);
        }

        return $this->jsonSuccess();
    }
}
