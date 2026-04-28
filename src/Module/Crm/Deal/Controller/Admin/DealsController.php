<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Crm\Deal\Contract\DealManagerInterface;
use Aurora\Module\Crm\Deal\DTO\DealInput;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Deal\Repository\DealRepository;
use Aurora\Module\Crm\Deal\Serializer\DealSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/deals', name: 'crm_deals')]
#[IsGranted('crm.deals.manage')]
final class DealsController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly DealSerializer $dealSerializer,
        private readonly DealManagerInterface $dealManager,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->renderApp($pagination, initialView: 'list', kanbanColumns: null);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->buildListPayload($pagination));
    }

    #[Route('/kanban-columns', name: '_kanban_columns', methods: [HttpMethodEnum::Get->value])]
    public function kanbanColumns(): JsonResponse
    {
        return $this->json(['columns' => $this->buildKanbanColumns()]);
    }

    public function renderApp(PaginationRequest $pagination, string $initialView, ?array $kanbanColumns): Response
    {
        return $this->render('@Crm/admin/deals/index.html.twig', [
            'initialView' => $initialView,
            'kanbanColumns' => $kanbanColumns,
            'deals' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'stages' => array_map(static fn (DealStageEnum $stage): string => $stage->value, DealStageEnum::cases()),
            'createPath' => $this->generateUrl('crm_deals_create'),
            'updatePath' => $this->generateUrl('crm_deals_update', ['id' => '__id__']),
            'deletePath' => $this->generateUrl('crm_deals_delete', ['id' => '__id__']),
            'listPath' => $this->generateUrl('crm_deals_list'),
            'kanbanColumnsPath' => $this->generateUrl('crm_deals_kanban_columns'),
            'contactsListPath' => $this->generateUrl('crm_contacts_list'),
            'companiesListPath' => $this->generateUrl('crm_companies_list'),
        ]);
    }

    public function buildKanbanColumns(): array
    {
        $stages = DealStageEnum::cases();
        $columns = [];
        foreach ($stages as $stage) {
            $result = $this->dealRepository->findPaginated(1, 100, stage: $stage);
            $columns[$stage->value] = array_map($this->dealSerializer->serialize(...), $result['items']);
        }

        return $columns;
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = DealInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $deal = $this->dealManager->create($input);

        return $this->json(['success' => true, 'deal' => $this->dealSerializer->serialize($deal)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Deal $deal, Request $request): JsonResponse
    {
        $input = DealInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $this->dealManager->update($deal, $input);

        return $this->json(['success' => true, 'deal' => $this->dealSerializer->serialize($deal)]);
    }

    #[Route('/{id}/stage', name: '_stage', methods: [HttpMethodEnum::Patch->value])]
    public function updateStage(Deal $deal, Request $request): JsonResponse
    {
        $stage = DealStageEnum::tryFrom($this->decodeJson($request)['stage'] ?? '');
        if (null === $stage) {
            return $this->json(['success' => false, 'error' => 'Invalid stage'], 400);
        }

        $this->dealManager->changeStage($deal, $stage);

        return $this->json(['success' => true, 'deal' => $this->dealSerializer->serialize($deal)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Deal $deal): JsonResponse
    {
        $this->dealManager->delete($deal);

        return $this->json(['success' => true]);
    }

    private function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->dealRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'ok' => true,
            'items' => array_map($this->dealSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
