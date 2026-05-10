<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\MessageHandler;

use Aurora\Module\Billing\Ocr\Manager\OcrJobManagerInterface;
use Aurora\Module\Billing\Ocr\Message\RecoverStuckOcrJobsMessage;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Recovers OCR jobs stuck in Extracting or Parsing status.
 *
 * A job is considered stuck when its startedAt is older than MAX_AGE_MINUTES.
 * This happens when the worker crashes mid-pipeline (OOM, timeout, etc.).
 * Stuck jobs are marked as Failed so the user can retry them manually.
 */
#[AsMessageHandler]
final readonly class RecoverStuckOcrJobsHandler
{
    private const int MAX_AGE_MINUTES = 60;

    public function __construct(
        private OcrJobRepository $repository,
        private OcrJobManagerInterface $manager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(RecoverStuckOcrJobsMessage $message): void
    {
        $stuckJobs = $this->repository->findStuck(self::MAX_AGE_MINUTES);

        if ([] === $stuckJobs) {
            return;
        }

        foreach ($stuckJobs as $job) {
            $this->manager->markFailed($job, sprintf(
                'Job automatically marked as failed after being stuck in "%s" status for more than %d minutes.',
                $job->getStatus()->value,
                self::MAX_AGE_MINUTES,
            ));
        }

        $this->logger->warning('RecoverStuckOcrJobs: marked {count} stuck job(s) as failed.', [
            'count' => count($stuckJobs),
        ]);
    }
}
