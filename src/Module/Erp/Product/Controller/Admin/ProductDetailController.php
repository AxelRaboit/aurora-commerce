<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Controller\Admin;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Serializer\ProductActivitySerializer;
use Aurora\Module\Erp\Product\Serializer\ProductSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/erp/products/{id}', name: 'erp_products_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('erp.products.view')]
final class ProductDetailController extends AbstractController
{
    public function __construct(
        private readonly ProductSerializer $productSerializer,
        private readonly AuditLogRepository $auditLogRepository,
    ) {}

    public function __invoke(Product $product): Response
    {
        $result = $this->auditLogRepository->findPaginatedForEntity('Product', (int) $product->getId(), 1, 10);

        return $this->render('@Erp/admin/products/show.html.twig', [
            'product' => $this->productSerializer->serialize($product),
            'activity' => ProductActivitySerializer::serialize($result),
            'backPath' => $this->generateUrl('erp_products'),
            'updatePath' => $this->generateUrl('erp_products_update', ['id' => $product->getId()]),
            'deletePath' => $this->generateUrl('erp_products_delete', ['id' => $product->getId()]),
            'activityPath' => $this->generateUrl('erp_products_activity', ['id' => $product->getId()]),
        ]);
    }
}
