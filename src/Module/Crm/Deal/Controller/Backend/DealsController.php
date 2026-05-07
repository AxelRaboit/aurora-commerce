<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Crm\Deal\Contract\DealManagerInterface;
use Aurora\Module\Crm\Deal\DTO\DealInput;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Deal\Serializer\DealSerializer;
use Aurora\Module\Crm\Deal\View\DealsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/crm/deals', name: 'backend_crm_deals')]
#[IsGranted('crm.deals.manage')]
final class DealsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly DealSerializer $dealSerializer,
        private readonly DealManagerInterface $dealManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly DealsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Crm/backend/deals/index.html.twig', $this->viewBuilder->indexView($pagination, initialView: 'list', kanbanColumns: null));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination));
    }

    #[Route('/kanban-columns', name: '_kanban_columns', methods: [HttpMethodEnum::Get->value])]
    public function kanbanColumns(): JsonResponse
    {
        return $this->json(['columns' => $this->viewBuilder->buildKanbanColumns()]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = DealInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $deal = $this->dealManager->create($input);

        return $this->jsonSuccess(['deal' => $this->dealSerializer->serialize($deal)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Deal $deal, Request $request): JsonResponse
    {
        $input = DealInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->dealManager->update($deal, $input);

        return $this->jsonSuccess(['deal' => $this->dealSerializer->serialize($deal)]);
    }

    #[Route('/{id}/stage', name: '_stage', methods: [HttpMethodEnum::Patch->value])]
    public function updateStage(Deal $deal, Request $request): JsonResponse
    {
        $stage = DealStageEnum::tryFrom($this->decodeJson($request)['stage'] ?? '');
        if (null === $stage) {
            return $this->jsonFailure('Invalid stage', 400);
        }

        $this->dealManager->changeStage($deal, $stage);

        return $this->jsonSuccess(['deal' => $this->dealSerializer->serialize($deal)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Deal $deal): JsonResponse
    {
        $this->dealManager->delete($deal);

        return $this->jsonSuccess();
    }
}
