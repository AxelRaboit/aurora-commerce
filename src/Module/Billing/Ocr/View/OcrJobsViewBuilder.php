<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Aurora\Module\Billing\Ocr\Serializer\OcrJobSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function is_string;

final readonly class OcrJobsViewBuilder
{
    public function __construct(
        private OcrJobRepository $jobRepository,
        private OcrJobSerializer $jobSerializer,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function indexView(PaginationRequest $pagination, Request $request): array
    {
        return [
            'jobs' => $this->buildListPayload($pagination, $request),
            'listPath' => $this->urlGenerator->generate('billing_ocr_jobs_list'),
            'statusUrlTemplate' => $this->urlGenerator->generate('billing_ocr_jobs_status', ['id' => '__id__']),
            'retryPath' => $this->urlGenerator->generate('billing_ocr_jobs_retry', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('billing_ocr_jobs_delete', ['id' => '__id__']),
            'invoicesPath' => $this->urlGenerator->generate('billing_invoices'),
            'invoiceShowPath' => $this->urlGenerator->generate('billing_invoices_show', ['id' => '__id__']),
            'importPath' => $this->urlGenerator->generate('billing_ocr_import'),
            'statusOptions' => array_map(fn (OcrJobStatusEnum $status): array => [
                'value' => $status->value,
                'label' => $this->translator->trans($status->getLabelKey()),
                'color' => $status->getBadgeColor(),
            ], OcrJobStatusEnum::cases()),
        ];
    }

    public function buildListPayload(PaginationRequest $pagination, Request $request): array
    {
        $status = $this->resolveStatus($request->query->get('status'));
        $result = $this->jobRepository->findPaginated($pagination->page, $pagination->limit, $status);

        return [
            'success' => true,
            'items' => array_map($this->jobSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'status' => $status?->value,
        ];
    }

    private function resolveStatus(mixed $value): ?OcrJobStatusEnum
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        return OcrJobStatusEnum::tryFrom($value);
    }
}
