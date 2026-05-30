<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Dashboard;

use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Module\Erp\Product\Repository\ProductRepository;

/**
 * ERP slice of the backend dashboard. Lives in the Erp module so the General
 * dashboard never imports ERP repositories.
 */
final readonly class ErpStatsProvider implements DashboardStatsProviderInterface
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'erp';
    }

    public function getStats(): array
    {
        $byStatus = $this->productRepository->countByStatus();

        return [
            'erp' => [
                'products' => array_sum($byStatus),
                'draft' => $byStatus['draft'] ?? 0,
                'active' => $byStatus['active'] ?? 0,
                'archived' => $byStatus['archived'] ?? 0,
                'inventoryCents' => $this->productRepository->getTotalInventoryCents(),
                'outOfStock' => $this->productRepository->countOutOfStock(),
                'byType' => $this->productRepository->countByType(),
            ],
        ];
    }
}
