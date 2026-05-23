<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Import\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportPreview;
use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportRow;
use Aurora\Module\PersonalFinance\Import\Service\PersonalFinanceImportServiceInterface;
use Aurora\Module\PersonalFinance\Import\View\PersonalFinanceImportViewBuilder;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use const JSON_THROW_ON_ERROR;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.import.use')]
final class PersonalFinanceImportController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceImportServiceInterface $importService,
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PersonalFinanceImportViewBuilder $viewBuilder,
    ) {}

    #[Route('/import', name: '_import', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render(
            '@PersonalFinance/backend/import/index.html.twig',
            $this->viewBuilder->indexView($user),
        );
    }

    #[Route('/import/template', name: '_import_template', methods: [HttpMethodEnum::Get->value])]
    public function template(): Response
    {
        $content = $this->importService->buildTemplateContent();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="personal-finance-import-template.xlsx"');
        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }

    #[Route('/wallets/{walletId}/import/preview', name: '_wallets_import_preview', methods: [HttpMethodEnum::Post->value])]
    public function preview(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        $file = $request->files->get('file');
        if (null === $file) {
            return $this->jsonInvalidInput(['file' => 'personal_finance.import.errors.file_required']);
        }

        $preview = $this->importService->parseUpload($wallet, $file);

        return $this->jsonSuccess([
            'preview' => $this->serializePreview($preview),
            // Round-trip the parsed rows so /process doesn't have to re-parse the upload —
            // the client just POSTs them back. Cheaper UX (no second file upload) + the
            // service treats the wire payload as authoritative.
            'rowsForProcess' => array_map($this->serializeRowForProcess(...), $preview->rows),
        ]);
    }

    #[Route('/wallets/{walletId}/import/process', name: '_wallets_import_process', methods: [HttpMethodEnum::Post->value])]
    public function process(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($payload) || !isset($payload['rows']) || !is_array($payload['rows'])) {
            return $this->jsonInvalidInput(['rows' => 'personal_finance.import.errors.rows_required']);
        }

        $rows = [];
        foreach ($payload['rows'] as $rawRow) {
            if (!is_array($rawRow)) {
                continue;
            }

            $rows[] = $this->hydrateRow($rawRow);
        }

        $preview = new PersonalFinanceImportPreview($rows, [], []);
        $report = $this->importService->process($user, $wallet, $preview);

        return $this->jsonSuccess([
            'createdCount' => $report->createdCount,
            'skippedCount' => $report->skippedCount,
            'categoriesCreated' => $report->categoriesCreated,
            'skippedRows' => $report->skippedRows,
        ]);
    }

    /** @return array<string, mixed> */
    private function serializePreview(PersonalFinanceImportPreview $preview): array
    {
        return [
            'validCount' => $preview->validRowCount(),
            'invalidCount' => $preview->invalidRowCount(),
            'newCategoryNames' => $preview->newCategoryNames,
            'fatalErrors' => $preview->fatalErrors,
            'rows' => array_map(fn (PersonalFinanceImportRow $r): array => [
                'rowNumber' => $r->rowNumber,
                'date' => $r->date?->format('Y-m-d'),
                'type' => $r->type?->value,
                'amount' => $r->amount,
                'categoryName' => $r->categoryName,
                'description' => $r->description,
                'tags' => $r->tags,
                'errors' => $r->errors,
                'valid' => $r->isValid(),
            ], $preview->rows),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeRowForProcess(PersonalFinanceImportRow $row): array
    {
        return [
            'rowNumber' => $row->rowNumber,
            'date' => $row->date?->format('Y-m-d'),
            'type' => $row->type?->value,
            'amount' => $row->amount,
            'categoryName' => $row->categoryName,
            'description' => $row->description,
            'tags' => $row->tags,
            'errors' => $row->errors,
        ];
    }

    /** @param array<string, mixed> $raw */
    private function hydrateRow(array $raw): PersonalFinanceImportRow
    {
        $date = null;
        if (is_string($raw['date'] ?? null) && '' !== $raw['date']) {
            try {
                $date = new DateTimeImmutable($raw['date']);
            } catch (Throwable) {
                $date = null;
            }
        }

        $type = is_string($raw['type'] ?? null) ? PersonalFinanceTransactionTypeEnum::tryFrom($raw['type']) : null;
        $tags = is_array($raw['tags'] ?? null) ? array_values(array_filter(array_map(strval(...), $raw['tags']))) : [];
        $errors = is_array($raw['errors'] ?? null) ? array_values(array_filter(array_map(strval(...), $raw['errors']))) : [];

        return new PersonalFinanceImportRow(
            rowNumber: (int) ($raw['rowNumber'] ?? 0),
            date: $date,
            type: $type,
            amount: is_string($raw['amount'] ?? null) ? $raw['amount'] : null,
            categoryName: is_string($raw['categoryName'] ?? null) && '' !== $raw['categoryName'] ? $raw['categoryName'] : null,
            description: is_string($raw['description'] ?? null) && '' !== $raw['description'] ? $raw['description'] : null,
            tags: $tags,
            errors: $errors,
            rawValues: [],
        );
    }
}
