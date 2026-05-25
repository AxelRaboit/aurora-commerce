<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Aurora\Module\Ged\Document\Contract\DocumentUsageProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Reports OCR jobs whose source file is the given GED document
 * (`BillingOcrJob.document`).
 */
final readonly class BillingOcrDocumentUsageProvider implements DocumentUsageProviderInterface
{
    public function __construct(
        private OcrJobRepository $ocrJobRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function findUsages(int $documentId): array
    {
        $jobs = $this->ocrJobRepository->createQueryBuilder('j')
            ->andWhere('j.document = :id')
            ->setParameter('id', $documentId)
            ->getQuery()
            ->getResult();

        $usages = [];
        foreach ($jobs as $job) {
            $usages[] = [
                'type' => 'billing.ocr',
                'label' => $job->getReference() ?? '#'.$job->getId(),
                'detail' => $this->translator->trans('backend.ged.documents.usage.billing_ocr'),
                'href' => $this->safeUrl('backend_billing_ocr_jobs', []),
            ];
        }

        return $usages;
    }

    /** @param array<string, mixed> $params */
    private function safeUrl(string $route, array $params): ?string
    {
        try {
            return $this->urlGenerator->generate($route, $params);
        } catch (Throwable) {
            return null;
        }
    }
}
