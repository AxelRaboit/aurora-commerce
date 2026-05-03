<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Enum;

use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OcrJobStatusEnumTest extends TestCase
{
    /**
     * @return iterable<string, array{OcrJobStatusEnum, bool}>
     */
    public static function terminalCases(): iterable
    {
        yield 'queued' => [OcrJobStatusEnum::Queued, false];
        yield 'extracting' => [OcrJobStatusEnum::Extracting, false];
        yield 'parsing' => [OcrJobStatusEnum::Parsing, false];
        yield 'completed' => [OcrJobStatusEnum::Completed, true];
        yield 'needs_review' => [OcrJobStatusEnum::NeedsReview, true];
        yield 'failed' => [OcrJobStatusEnum::Failed, true];
    }

    #[DataProvider('terminalCases')]
    public function testIsTerminal(OcrJobStatusEnum $status, bool $expected): void
    {
        self::assertSame($expected, $status->isTerminal());
    }

    public function testEveryStatusHasBadgeColor(): void
    {
        $allowed = ['accent', 'rose', 'sky', 'amber', 'emerald', 'violet', 'slate', 'gray'];
        foreach (OcrJobStatusEnum::cases() as $case) {
            self::assertContains(
                $case->getBadgeColor(),
                $allowed,
                \sprintf('Status %s has unsupported badge colour %s', $case->value, $case->getBadgeColor()),
            );
        }
    }

    public function testLabelKeyFollowsConvention(): void
    {
        self::assertSame('admin.billing.ocr.status.queued', OcrJobStatusEnum::Queued->getLabelKey());
        self::assertSame('admin.billing.ocr.status.needs_review', OcrJobStatusEnum::NeedsReview->getLabelKey());
    }
}
