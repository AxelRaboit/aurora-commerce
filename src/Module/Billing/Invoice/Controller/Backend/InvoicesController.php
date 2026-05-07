<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Billing\Invoice\Contract\InvoiceLineManagerInterface;
use Aurora\Module\Billing\Invoice\Contract\InvoiceManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Serializer\InvoiceSerializer;
use Aurora\Module\Billing\Invoice\Service\InvoiceXlsxExporter;
use Aurora\Module\Billing\Invoice\View\InvoicesViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use function is_array;

#[Route('/backend/billing/invoices', name: 'backend_billing_invoices')]
#[IsGranted('billing.invoices.view')]
final class InvoicesController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly InvoicesViewBuilder $viewBuilder,
        private readonly InvoiceSerializer $invoiceSerializer,
        private readonly InvoiceManagerInterface $invoiceManager,
        private readonly InvoiceLineManagerInterface $lineManager,
        private readonly InvoiceXlsxExporter $xlsxExporter,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $pagination = PaginationRequest::fromRequest($request);

        return $this->render('@Billing/backend/invoices/index.html.twig', $this->viewBuilder->indexView($pagination, $request));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        $pagination = PaginationRequest::fromRequest($request);

        return $this->json($this->viewBuilder->buildListPayload($pagination, $request));
    }

    #[Route('/export.xlsx', name: '_export_xlsx', methods: [HttpMethodEnum::Get->value])]
    public function exportXlsx(Request $request): Response
    {
        return $this->xlsxExporter->buildResponse($this->viewBuilder->findForExport($request));
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(Invoice $invoice): Response
    {
        return $this->render('@Billing/backend/invoices/show.html.twig', $this->viewBuilder->showView($invoice));
    }

    #[Route('/{id}/validate', name: '_validate', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.edit')]
    public function validateInvoice(Invoice $invoice): JsonResponse
    {
        $this->invoiceManager->validate($invoice);

        return $this->jsonSuccess(['invoice' => $this->invoiceSerializer->serializeDetail($invoice)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.edit')]
    public function update(Invoice $invoice, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['field'])) {
            return $this->jsonInvalidInput(['field' => 'backend.billing.invoices.update.fieldRequired']);
        }

        try {
            $this->invoiceManager->updateField($invoice, (string) $payload['field'], $payload['value'] ?? null);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput([(string) $payload['field'] => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['invoice' => $this->invoiceSerializer->serializeDetail($invoice)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.delete')]
    public function delete(Invoice $invoice, Request $request): JsonResponse
    {
        if (!$invoice->getStatus()->isDeletable()) {
            return $this->jsonFailure('backend.billing.invoices.deleteError');
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $deleteTiers = (bool) ($body['deleteTiers'] ?? false);
        $deleteBuyer = (bool) ($body['deleteBuyer'] ?? false);

        try {
            $this->invoiceManager->delete($invoice, $deleteTiers, $deleteBuyer);
        } catch (Throwable $throwable) {
            return $this->jsonFailure('backend.billing.invoices.deleteError', extra: ['detail' => $throwable->getMessage()]);
        }

        return $this->jsonSuccess();
    }

    #[Route('/{id}/lines/create', name: '_lines_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.edit')]
    public function createLine(Invoice $invoice): JsonResponse
    {
        try {
            $this->lineManager->add($invoice);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['invoice' => $this->invoiceSerializer->serializeDetail($invoice)]);
    }

    #[Route('/{id}/lines/{lineId}/update', name: '_lines_update', requirements: ['id' => '\d+|__id__', 'lineId' => '\d+|__lineId__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.edit')]
    public function updateLine(Invoice $invoice, int $lineId, Request $request): JsonResponse
    {
        $line = $this->resolveLine($invoice, $lineId);
        if (!$line instanceof InvoiceLine) {
            return $this->jsonNotFound();
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['field'])) {
            return $this->jsonInvalidInput(['field' => 'backend.billing.invoices.update.fieldRequired']);
        }

        try {
            $this->lineManager->updateField($line, (string) $payload['field'], $payload['value'] ?? null);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput([(string) $payload['field'] => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['invoice' => $this->invoiceSerializer->serializeDetail($invoice)]);
    }

    #[Route('/{id}/lines/{lineId}/delete', name: '_lines_delete', requirements: ['id' => '\d+|__id__', 'lineId' => '\d+|__lineId__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.edit')]
    public function deleteLine(Invoice $invoice, int $lineId): JsonResponse
    {
        $line = $this->resolveLine($invoice, $lineId);
        if (!$line instanceof InvoiceLine) {
            return $this->jsonNotFound();
        }

        try {
            $this->lineManager->delete($line);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['invoice' => $this->invoiceSerializer->serializeDetail($invoice)]);
    }

    #[Route('/{id}/credit-note', name: '_credit_note', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.edit')]
    public function createCreditNote(Invoice $invoice, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $reason = is_array($payload) ? ($payload['reason'] ?? null) : null;

        try {
            $creditNote = $this->invoiceManager->createCreditNote($invoice, $reason);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess([
            'invoice' => $this->invoiceSerializer->serializeDetail($invoice),
            'creditNote' => $this->invoiceSerializer->serializeDetail($creditNote),
        ]);
    }

    /**
     * Find a line that belongs to the given invoice. Refusing cross-invoice
     * line ids prevents IDOR via crafted URLs.
     */
    private function resolveLine(Invoice $invoice, int $lineId): ?InvoiceLine
    {
        foreach ($invoice->getLines() as $line) {
            if ($line->getId() === $lineId) {
                return $line;
            }
        }

        return null;
    }
}
