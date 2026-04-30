<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin orders index view.
 */
final readonly class OrdersViewBuilder
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @param array<string, mixed> $listPayload
     *
     * @return array<string, mixed>
     */
    public function indexView(PaginationRequest $pagination, ?OrderStatusEnum $status, array $listPayload): array
    {
        return [
            'orders' => $listPayload,
            'search' => $pagination->search ?? '',
            'currentStatus' => $status instanceof OrderStatusEnum ? $status->value : '',
            'stats' => $this->orderRepository->countByStatus(),
            'showPath' => $this->urlGenerator->generate('ecommerce_orders_show', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('ecommerce_orders_list'),
        ];
    }
}
