<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Deal\Enum;

use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use PHPUnit\Framework\TestCase;

final class DealStageEnumTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('lead', DealStageEnum::Lead->value);
        self::assertSame('qualified', DealStageEnum::Qualified->value);
        self::assertSame('proposal', DealStageEnum::Proposal->value);
        self::assertSame('negotiation', DealStageEnum::Negotiation->value);
        self::assertSame('won', DealStageEnum::Won->value);
        self::assertSame('lost', DealStageEnum::Lost->value);
        self::assertCount(6, DealStageEnum::cases());
    }
}
