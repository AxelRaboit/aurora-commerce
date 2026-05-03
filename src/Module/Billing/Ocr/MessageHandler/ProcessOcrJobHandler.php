<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\MessageHandler;

use Aurora\Module\Billing\Ocr\Contract\OcrJobManagerInterface;
use Aurora\Module\Billing\Ocr\Message\ProcessOcrJobMessage;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Aurora\Module\Billing\Ocr\Service\OcrPipeline;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Throwable;

use function sprintf;

#[AsMessageHandler]
final readonly class ProcessOcrJobHandler
{
    public function __construct(
        private OcrJobRepository $jobs,
        private OcrPipeline $pipeline,
        private OcrJobManagerInterface $jobManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ProcessOcrJobMessage $message): void
    {
        $job = $this->jobs->find($message->ocrJobId);
        if (null === $job) {
            // Job was deleted between dispatch and consume — nothing to do, don't retry.
            throw new UnrecoverableMessageHandlingException(sprintf('OcrJob %d not found', $message->ocrJobId));
        }

        if ($job->getStatus()->isTerminal()) {
            return;
        }

        try {
            $this->pipeline->run($job);
        } catch (Throwable $throwable) {
            $this->logger->error('OCR job failed', ['job_id' => $job->getId(), 'exception' => $throwable]);
            $job->appendLog('error', 'Erreur : '.$throwable->getMessage());
            $this->jobManager->markFailed($job, $throwable->getMessage());

            throw $throwable;
        }
    }
}
