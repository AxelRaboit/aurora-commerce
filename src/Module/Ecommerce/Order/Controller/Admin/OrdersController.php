<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\Serializer\OrderSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ecommerce/orders', name: 'ecommerce_orders')]
#[IsGranted('ecommerce.orders.view')]
final class OrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderSerializer $orderSerializer,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $status = OrderStatusEnum::tryFrom((string) $request->query->get('status', ''));

        return $this->render('@Ecommerce/admin/orders/index.html.twig', [
            'orders' => $this->buildListPayload($pagination, $status),
            'search' => $pagination->search ?? '',
            'currentStatus' => null === $status ? '' : $status->value,
            'stats' => $this->orderRepository->countByStatus(),
            'showPath' => $this->generateUrl('ecommerce_orders_show', ['id' => '__id__']),
            'listPath' => $this->generateUrl('ecommerce_orders_list'),
        ]);
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
            'ok' => true,
            'items' => array_map($this->orderSerializer->serializeForList(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
