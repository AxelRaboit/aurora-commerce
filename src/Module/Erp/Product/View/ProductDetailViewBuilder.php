<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\View;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Serializer\ProductActivitySerializer;
use Aurora\Module\Erp\Product\Serializer\ProductSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin product detail view. Centralises
 * URL generation + serialisation for the show screen.
 */
final readonly class ProductDetailViewBuilder
{
    public function __construct(
        private ProductSerializer $productSerializer,
        private AuditLogRepository $auditLogRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(Product $product): array
    {
        $result = $this->auditLogRepository->findPaginatedForEntity('Product', (int) $product->getId(), 1, 10);

        return [
            'product' => $this->productSerializer->serialize($product),
            'activity' => ProductActivitySerializer::serialize($result),
            'backPath' => $this->urlGenerator->generate('backend_erp_products'),
            'updatePath' => $this->urlGenerator->generate('backend_erp_products_update', ['id' => $product->getId()]),
            'deletePath' => $this->urlGenerator->generate('backend_erp_products_delete', ['id' => $product->getId()]),
            'activityPath' => $this->urlGenerator->generate('backend_erp_products_activity', ['id' => $product->getId()]),
        ];
    }
}
