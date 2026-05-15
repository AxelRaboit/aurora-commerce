<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Billing\Invoice\Enum;

use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use PHPUnit\Framework\TestCase;

final class TiersTypeEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.billing.tiers.type.supplier', TiersTypeEnum::Supplier->getLabelKey());
        self::assertSame('backend.billing.tiers.type.client', TiersTypeEnum::Client->getLabelKey());
        self::assertSame('backend.billing.tiers.type.partner', TiersTypeEnum::Partner->getLabelKey());
        self::assertSame('backend.billing.tiers.type.subcontractor', TiersTypeEnum::Subcontractor->getLabelKey());
        self::assertSame('backend.billing.tiers.type.other', TiersTypeEnum::Other->getLabelKey());
    }
}
