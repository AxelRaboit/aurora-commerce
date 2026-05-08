<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Controller\Backend;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Erp\Product\Dto\ProductInputFactoryInterface;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Manager\ProductManagerInterface;
use Aurora\Module\Erp\Product\Serializer\ProductActivitySerializer;
use Aurora\Module\Erp\Product\Serializer\ProductSerializerInterface;
use Aurora\Module\Erp\Product\View\ProductsViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/erp/products', name: 'backend_erp_products')]
#[IsGranted('erp.products.view')]
final class ProductsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly ProductSerializerInterface $productSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly ProductManagerInterface $productManager,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly ProductsViewBuilder $viewBuilder,
        private readonly ProductInputFactoryInterface $productInputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        return $this->render('@Erp/backend/products/index.html.twig', $this->viewBuilder->indexView($pagination, $request));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination, $request));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('erp.products.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->productInputFactory->fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $product = $this->productManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['reference' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['product' => $this->productSerializer->serialize($product)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('erp.products.edit')]
    public function update(Product $product, Request $request): JsonResponse
    {
        $input = $this->productInputFactory->fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->productManager->update($product, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['reference' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['product' => $this->productSerializer->serialize($product)]);
    }

    #[Route('/{id}/activity', name: '_activity', requirements: ['id' => '\d+'], methods: [HttpMethodEnum::Get->value])]
    public function activity(Product $product, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->auditLogRepository->findPaginatedForEntity('Product', (int) $product->getId(), $page, 10);

        return $this->jsonSuccess(ProductActivitySerializer::serialize($result));
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('erp.products.delete')]
    public function delete(Product $product): JsonResponse
    {
        $this->productManager->delete($product);

        return $this->jsonSuccess();
    }
}
