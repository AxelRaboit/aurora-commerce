<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Controller\Admin;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Erp\Product\Contract\ProductManagerInterface;
use Aurora\Module\Erp\Product\DTO\ProductInput;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Aurora\Module\Erp\Product\Serializer\ProductActivitySerializer;
use Aurora\Module\Erp\Product\Serializer\ProductSerializer;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/erp/products', name: 'erp_products')]
#[IsGranted('erp.products.view')]
final class ProductsController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductSerializer $productSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly ProductManagerInterface $productManager,
        private readonly AuditLogRepository $auditLogRepository,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $payload = $this->buildListPayload($pagination, $request);

        return $this->render('@Erp/admin/products/index.html.twig', [
            'products' => $payload,
            'search' => $pagination->search ?? '',
            'createPath' => $this->generateUrl('erp_products_create'),
            'updatePath' => $this->generateUrl('erp_products_update', ['id' => '__id__']),
            'deletePath' => $this->generateUrl('erp_products_delete', ['id' => '__id__']),
            'showPath' => $this->generateUrl('erp_products_show', ['id' => '__id__']),
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        return $this->json($this->buildListPayload($pagination, $request));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('erp.products.create')]
    public function create(Request $request): JsonResponse
    {
        $input = ProductInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $product = $this->productManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['sku' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'product' => $this->productSerializer->serialize($product)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('erp.products.edit')]
    public function update(Product $product, Request $request): JsonResponse
    {
        $input = ProductInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->productManager->update($product, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['sku' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'product' => $this->productSerializer->serialize($product)]);
    }

    #[Route('/{id}/activity', name: '_activity', requirements: ['id' => '\d+'], methods: [HttpMethodEnum::Get->value])]
    public function activity(Product $product, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->auditLogRepository->findPaginatedForEntity('Product', (int) $product->getId(), $page, 10);

        return $this->json(['ok' => true, ...ProductActivitySerializer::serialize($result)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('erp.products.delete')]
    public function delete(Product $product): JsonResponse
    {
        $this->productManager->delete($product);

        return $this->json(['success' => true]);
    }

    /** @return array{ok: bool, items: list<array<string, mixed>>, total: int, page: int, totalPages: int, status: ?string} */
    private function buildListPayload(PaginationRequest $pagination, Request $request): array
    {
        $status = $this->resolveStatus($request->query->get('status'));
        $result = $this->productRepository->findPaginated($pagination->page, search: $pagination->search, status: $status);

        return [
            'ok' => true,
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
