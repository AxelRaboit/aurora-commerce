<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\View\ProductDetailViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/erp/products/{id}', name: 'erp_products_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('erp.products.view')]
final class ProductDetailController extends AbstractController
{
    public function __construct(
        private readonly ProductDetailViewBuilder $viewBuilder,
    ) {}

    public function __invoke(Product $product): Response
    {
        return $this->render('@Erp/admin/products/show.html.twig', $this->viewBuilder->showView($product));
    }
}
