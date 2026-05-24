<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Goal\Dto\PersonalFinanceGoalDepositInputFactoryInterface;
use Aurora\Module\PersonalFinance\Goal\Dto\PersonalFinanceGoalInputFactoryInterface;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;
use Aurora\Module\PersonalFinance\Goal\Manager\PersonalFinanceGoalManagerInterface;
use Aurora\Module\PersonalFinance\Goal\Repository\PersonalFinanceGoalRepository;
use Aurora\Module\PersonalFinance\Goal\Serializer\PersonalFinanceGoalSerializerInterface;
use Aurora\Module\PersonalFinance\Goal\View\PersonalFinanceGoalsViewBuilder;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/goals', name: 'backend_personal_finance_goals')]
#[IsGranted('personal_finance.goals.use')]
final class PersonalFinanceGoalsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceGoalManagerInterface $goalManager,
        private readonly PersonalFinanceGoalRepository $goalRepository,
        private readonly PersonalFinanceGoalSerializerInterface $goalSerializer,
        private readonly PersonalFinanceGoalInputFactoryInterface $goalInputFactory,
        private readonly PersonalFinanceGoalDepositInputFactoryInterface $depositInputFactory,
        private readonly PersonalFinanceGoalsViewBuilder $viewBuilder,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render(
            '@PersonalFinance/backend/goals/index.html.twig',
            $this->viewBuilder->indexView($user),
        );
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->goalInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $goal = $this->goalManager->create($user, $input);

        return $this->jsonSuccess(['goal' => $this->goalSerializer->serialize($goal)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        $goal = $this->loadOwned($id);
        if (!$goal instanceof PersonalFinanceGoalInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->goalInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->goalManager->update($goal, $input);

        return $this->jsonSuccess(['goal' => $this->goalSerializer->serialize($goal)]);
    }

    #[Route('/{id}/deposit', name: '_deposit', methods: [HttpMethodEnum::Post->value])]
    public function deposit(int $id, Request $request): JsonResponse
    {
        $goal = $this->loadOwned($id);
        if (!$goal instanceof PersonalFinanceGoalInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->depositInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->goalManager->deposit($goal, $input);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['amount' => $domainException->getMessage()]);
        }

        return $this->jsonSuccess(['goal' => $this->goalSerializer->serialize($goal)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $goal = $this->loadOwned($id);
        if (!$goal instanceof PersonalFinanceGoalInterface) {
            return $this->jsonNotFound();
        }

        $this->goalManager->delete($goal);

        return $this->jsonSuccess();
    }

    /**
     * Loads a goal and ensures the current user is its owner. Returns
     * null when missing or owned by another user — controller maps to
     * jsonNotFound (no info leak about other users' goals).
     */
    private function loadOwned(int $id): ?PersonalFinanceGoalInterface
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $goal = $this->goalRepository->find($id);
        if (!$goal instanceof PersonalFinanceGoalInterface) {
            return null;
        }

        if ($goal->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $goal;
    }
}
