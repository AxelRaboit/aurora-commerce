<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Billing\Invoice\Contract\SupplierManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Supplier;
use Aurora\Module\Billing\Invoice\Serializer\SupplierSerializer;
use Aurora\Module\Billing\Invoice\View\SuppliersViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use function is_array;

#[Route('/admin/billing/suppliers', name: 'billing_suppliers')]
#[IsGranted('billing.suppliers.view')]
final class SuppliersController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SuppliersViewBuilder $viewBuilder,
        private readonly SupplierSerializer $supplierSerializer,
        private readonly SupplierManagerInterface $supplierManager,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $pagination = PaginationRequest::fromRequest($request);

        return $this->render('@Billing/admin/suppliers/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        $pagination = PaginationRequest::fromRequest($request);

        return $this->json($this->viewBuilder->buildListPayload($pagination));
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(Supplier $supplier): Response
    {
        return $this->render('@Billing/admin/suppliers/show.html.twig', $this->viewBuilder->showView($supplier));
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.suppliers.manage')]
    public function update(Supplier $supplier, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['field'])) {
            return $this->jsonInvalidInput(['field' => 'admin.billing.suppliers.update.fieldRequired']);
        }

        try {
            $this->supplierManager->updateField($supplier, (string) $payload['field'], $payload['value'] ?? null);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput([(string) $payload['field'] => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['supplier' => $this->supplierSerializer->serialize($supplier)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.suppliers.manage')]
    public function delete(Supplier $supplier): JsonResponse
    {
        try {
            $this->supplierManager->delete($supplier);
        } catch (Throwable $throwable) {
            return $this->jsonFailure('admin.billing.suppliers.deleteError', extra: ['detail' => $throwable->getMessage()]);
        }

        return $this->jsonSuccess();
    }
}
