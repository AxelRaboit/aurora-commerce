<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\Serializer\OrderSerializerInterface;
use Aurora\Module\Ecommerce\Order\View\OrdersViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/ecommerce/orders', name: 'backend_ecommerce_orders')]
#[IsGranted('ecommerce.orders.view')]
final class OrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderSerializerInterface $orderSerializer,
        private readonly OrdersViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $status = OrderStatusEnum::tryFrom((string) $request->query->get('status', ''));

        return $this->render('@Ecommerce/backend/orders/index.html.twig', $this->viewBuilder->indexView($pagination, $status, $this->buildListPayload($pagination, $status)));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        $status = OrderStatusEnum::tryFrom((string) $request->query->get('status', ''));

        return $this->json($this->buildListPayload($pagination, $status));
    }

    private function buildListPayload(PaginationRequest $pagination, ?OrderStatusEnum $status): array
    {
        $result = $this->orderRepository->findPaginated($pagination->page, search: $pagination->search, status: $status);

        return [
            'success' => true,
            'items' => array_map($this->orderSerializer->serializeForList(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
