<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Categorization\Dto\PersonalFinanceCategorizationRuleInputFactoryInterface;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use Aurora\Module\PersonalFinance\Categorization\Manager\PersonalFinanceCategorizationRuleManagerInterface;
use Aurora\Module\PersonalFinance\Categorization\Repository\PersonalFinanceCategorizationRuleRepository;
use Aurora\Module\PersonalFinance\Categorization\Serializer\PersonalFinanceCategorizationRuleSerializerInterface;
use Aurora\Module\PersonalFinance\Categorization\Service\PersonalFinanceCategorizationSuggestServiceInterface;
use Aurora\Module\PersonalFinance\Categorization\View\PersonalFinanceCategorizationRulesViewBuilder;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/categorization-rules', name: 'backend_personal_finance_categorization_rules')]
#[IsGranted('personal_finance.categorization.use')]
final class PersonalFinanceCategorizationRulesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceCategorizationRuleManagerInterface $ruleManager,
        private readonly PersonalFinanceCategorizationRuleRepository $ruleRepository,
        private readonly PersonalFinanceCategorizationRuleInputFactoryInterface $ruleInputFactory,
        private readonly PersonalFinanceCategorizationRuleSerializerInterface $ruleSerializer,
        private readonly PersonalFinanceCategorizationSuggestServiceInterface $suggestService,
        private readonly PersonalFinanceCategoryRepository $categoryRepository,
        private readonly PersonalFinanceCategorizationRulesViewBuilder $viewBuilder,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render(
            '@PersonalFinance/backend/categorization/index.html.twig',
            $this->viewBuilder->indexView($user, $pagination),
        );
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->json($this->viewBuilder->buildListPayload($user, $pagination));
    }

    #[Route('/suggest', name: '_suggest', methods: [HttpMethodEnum::Get->value])]
    public function suggest(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $description = (string) $request->query->get('description', '');

        $category = $this->suggestService->suggest($user, $description);

        return $this->jsonSuccess([
            'category' => $category instanceof PersonalFinanceCategoryInterface ? [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'walletId' => $category->getWallet()->getId(),
            ] : null,
        ]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        $rule = $this->loadOwned($id);
        if (!$rule instanceof PersonalFinanceCategorizationRuleInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->ruleInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $category = $this->categoryRepository->find($input->getCategoryId());
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return $this->jsonInvalidInput(['categoryId' => 'personal_finance.categorization.errors.category_not_found']);
        }
        if ($category->isSystem()) {
            return $this->jsonInvalidInput(['categoryId' => 'personal_finance.categorization.errors.category_system']);
        }

        $this->ruleManager->update($rule, $input, $category);

        return $this->jsonSuccess(['rule' => $this->ruleSerializer->serialize($rule)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $rule = $this->loadOwned($id);
        if (!$rule instanceof PersonalFinanceCategorizationRuleInterface) {
            return $this->jsonNotFound();
        }

        $this->ruleManager->delete($rule);

        return $this->jsonSuccess();
    }

    private function loadOwned(int $id): ?PersonalFinanceCategorizationRuleInterface
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $rule = $this->ruleRepository->find($id);
        if (!$rule instanceof PersonalFinanceCategorizationRuleInterface) {
            return null;
        }
        if ($rule->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $rule;
    }
}
