<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Enum;

use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use PHPUnit\Framework\TestCase;

final class InvoiceStatusEnumTest extends TestCase
{
    public function testEveryStatusHasBadgeColorAndLabelKey(): void
    {
        $allowed = ['accent', 'rose', 'sky', 'amber', 'emerald', 'violet', 'slate', 'gray'];
        foreach (InvoiceStatusEnum::cases() as $case) {
            self::assertContains($case->getBadgeColor(), $allowed);
            self::assertSame('admin.billing.invoices.status.'.$case->value, $case->getLabelKey());
        }
    }

    public function testValuesAreStable(): void
    {
        // These string values are persisted in DB — changing them would break existing data.
        self::assertSame('draft', InvoiceStatusEnum::Draft->value);
        self::assertSame('needs_review', InvoiceStatusEnum::NeedsReview->value);
        self::assertSame('validated', InvoiceStatusEnum::Validated->value);
        self::assertSame('paid', InvoiceStatusEnum::Paid->value);
        self::assertSame('archived', InvoiceStatusEnum::Archived->value);
    }
}
