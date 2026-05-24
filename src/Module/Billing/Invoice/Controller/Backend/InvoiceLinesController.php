<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use Aurora\Module\Billing\Invoice\Manager\InvoiceLineManagerInterface;
use Aurora\Module\Billing\Invoice\Serializer\InvoiceSerializer;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function is_array;

/**
 * Invoice lines sub-domain — create / update / delete lines on an
 * invoice. Split from `InvoicesController`. Route names preserved
 * (`backend_billing_invoices_lines_*`).
 */
#[Route('/backend/billing/invoices', name: 'backend_billing_invoices')]
#[IsGranted('billing.invoices.view')]
final class InvoiceLinesController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly InvoiceLineManagerInterface $lineManager,
        private readonly InvoiceSerializer $invoiceSerializer,
    ) {}

    #[Route('/{id}/lines/create', name: '_lines_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.invoices.edit')]
    public function create(Invoice $invoice): JsonResponse
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
    public function update(Invoice $invoice, int $lineId, Request $request): JsonResponse
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
    public function delete(Invoice $invoice, int $lineId): JsonResponse
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

    /**
     * Find a line that belongs to the given invoice. Refusing cross-invoice
     * line ids prevents IDOR via crafted URLs.
     */
    private function resolveLine(Invoice $invoice, int $lineId): ?InvoiceLineInterface
    {
        foreach ($invoice->getLines() as $line) {
            if ($line->getId() === $lineId) {
                return $line;
            }
        }

        return null;
    }
}
