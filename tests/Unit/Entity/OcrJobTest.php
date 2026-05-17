<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OcrJobTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new OcrJob())->getId());
    }

    public function testDefaultValues(): void
    {
        $job = new OcrJob();

        self::assertNull($job->getReference());
        self::assertSame(OcrJobStatusEnum::Queued, $job->getStatus());
        self::assertNull($job->getCreatedBy());
        self::assertNull($job->getModelUsed());
        self::assertNull($job->getStartedAt());
        self::assertNull($job->getFinishedAt());
        self::assertNull($job->getRawDoctr());
        self::assertNull($job->getRawVlm());
        self::assertNull($job->getExtracted());
        self::assertNull($job->getConfidence());
        self::assertNull($job->getError());
        self::assertSame([], $job->getLogs());
    }

    public function testMediaGetterAndSetter(): void
    {
        $media = $this->createStub(MediaInterface::class);
        $job = (new OcrJob())->setMedia($media);

        self::assertSame($media, $job->getMedia());
    }

    public function testStatusGetterAndSetter(): void
    {
        $job = (new OcrJob())->setStatus(OcrJobStatusEnum::Completed);

        self::assertSame(OcrJobStatusEnum::Completed, $job->getStatus());
    }

    public function testCreatedByGetterAndSetter(): void
    {
        $user = new User();
        $job = (new OcrJob())->setCreatedBy($user);

        self::assertSame($user, $job->getCreatedBy());
    }

    public function testModelUsedGetterAndSetter(): void
    {
        $job = (new OcrJob())->setModelUsed('gpt-4-vision');

        self::assertSame('gpt-4-vision', $job->getModelUsed());
    }

    public function testStartedAtAndFinishedAtGettersAndSetters(): void
    {
        $start = new DateTimeImmutable('2026-01-15 10:00:00');
        $finish = new DateTimeImmutable('2026-01-15 10:05:00');

        $job = (new OcrJob())->setStartedAt($start)->setFinishedAt($finish);

        self::assertSame($start, $job->getStartedAt());
        self::assertSame($finish, $job->getFinishedAt());
    }

    public function testRawDataGettersAndSetters(): void
    {
        $doctrData = ['blocks' => []];
        $vlmData = ['lines' => []];
        $extracted = ['amount' => 100];

        $job = (new OcrJob())
            ->setRawDoctr($doctrData)
            ->setRawVlm($vlmData)
            ->setExtracted($extracted);

        self::assertSame($doctrData, $job->getRawDoctr());
        self::assertSame($vlmData, $job->getRawVlm());
        self::assertSame($extracted, $job->getExtracted());
    }

    public function testConfidenceAndError(): void
    {
        $job = (new OcrJob())->setConfidence(0.95)->setError('OCR timeout');

        self::assertSame(0.95, $job->getConfidence());
        self::assertSame('OCR timeout', $job->getError());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $job = (new OcrJob())->setReference('OCR-001');

        self::assertSame('OCR-001', $job->getReference());
    }

    public function testAppendLogAddsEntry(): void
    {
        $job = new OcrJob();
        $job->appendLog('info', 'Started OCR');

        $logs = $job->getLogs();
        self::assertCount(1, $logs);
        self::assertSame('info', $logs[0]['level']);
        self::assertSame('Started OCR', $logs[0]['message']);
        self::assertSame([], $logs[0]['context']);
        self::assertArrayHasKey('ts', $logs[0]);
    }

    public function testAppendLogMultipleEntries(): void
    {
        $job = new OcrJob();
        $job->appendLog('info', 'Start', ['step' => 1]);
        $job->appendLog('error', 'Failed', ['code' => 500]);

        $logs = $job->getLogs();
        self::assertCount(2, $logs);
        self::assertSame(['step' => 1], $logs[0]['context']);
        self::assertSame(['code' => 500], $logs[1]['context']);
    }
}
