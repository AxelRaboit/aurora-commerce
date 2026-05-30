<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Dashboard;

use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;

/**
 * Ecommerce slice of the backend dashboard. Lives in the Ecommerce module so
 * the General dashboard never imports Ecommerce repositories.
 */
final readonly class EcommerceStatsProvider implements DashboardStatsProviderInterface
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ListingRepository $listingRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'ecommerce';
    }

    public function getStats(): array
    {
        $byStatus = $this->orderRepository->countByStatus();
        $totalOrders = array_sum($byStatus);
        $revenueCents = $this->orderRepository->getTotalRevenueCents();

        return [
            'ecommerce' => [
                'orders' => $totalOrders,
                'byStatus' => $byStatus,
                'listings' => $this->listingRepository->count([]),
                'revenueCents' => $revenueCents,
                'averageOrderCents' => $totalOrders > 0 ? (int) round($revenueCents / $totalOrders) : 0,
                'recentOrders' => $this->orderRepository->findRecent(5),
            ],
        ];
    }
}
