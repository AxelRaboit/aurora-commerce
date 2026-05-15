<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Contact\Enum;

use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use PHPUnit\Framework\TestCase;

final class ContactSourceEnumTest extends TestCase
{
    public function testGetLabelPrefixesValue(): void
    {
        self::assertSame('backend.crm.contacts.sources.manual', ContactSourceEnum::Manual->getLabel());
        self::assertSame('backend.crm.contacts.sources.form', ContactSourceEnum::Form->getLabel());
        self::assertSame('backend.crm.contacts.sources.order', ContactSourceEnum::Order->getLabel());
    }
}
