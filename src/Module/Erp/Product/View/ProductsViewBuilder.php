<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Aurora\Module\Erp\Product\Serializer\ProductSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin products list view. Centralises URL
 * generation + serialisation so the controller stays focused on HTTP flow.
 */
final readonly class ProductsViewBuilder
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductSerializer $productSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(PaginationRequest $pagination, Request $request): array
    {
        return [
            'products' => $this->buildListPayload($pagination, $request),
            'search' => $pagination->search ?? '',
            'createPath' => $this->urlGenerator->generate('erp_products_create'),
            'updatePath' => $this->urlGenerator->generate('erp_products_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('erp_products_delete', ['id' => '__id__']),
            'showPath' => $this->urlGenerator->generate('erp_products_show', ['id' => '__id__']),
        ];
    }

    /**
     * @return array{success: bool, items: list<array<string, mixed>>, total: int, page: int, totalPages: int, status: ?string}
     */
    public function buildListPayload(PaginationRequest $pagination, Request $request): array
    {
        $status = $this->resolveStatus($request->query->get('status'));
        $result = $this->productRepository->findPaginated($pagination->page, search: $pagination->search, status: $status);

        return [
            'success' => true,
            'items' => array_map($this->productSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'status' => $status?->value,
        ];
    }

    private function resolveStatus(?string $value): ?ProductStatusEnum
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return ProductStatusEnum::tryFrom($value);
    }
}
