<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\View;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Audit\Serializer\AuditLogSerializer;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Serializer\OrderSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin order detail view.
 */
final readonly class OrderDetailViewBuilder
{
    public function __construct(
        private OrderSerializerInterface $orderSerializer,
        private AuditLogRepository $auditLogRepository,
        private AuditLogSerializer $auditLogSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(Order $order): array
    {
        $activityResult = $this->auditLogRepository->findPaginatedForEntity('Order', $order->getId(), 1, 50);

        return [
            'order' => $this->orderSerializer->serialize($order),
            'activity' => array_map($this->auditLogSerializer->serialize(...), $activityResult['items']),
            'backPath' => $this->urlGenerator->generate('backend_ecommerce_orders'),
            'updateStatusPath' => $this->urlGenerator->generate('backend_ecommerce_orders_status', ['id' => $order->getId()]),
            'refundPath' => $this->urlGenerator->generate('backend_ecommerce_orders_refund', ['id' => $order->getId()]),
        ];
    }
}
