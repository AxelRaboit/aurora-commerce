<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\ContactTag\Entity;

use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use PHPUnit\Framework\TestCase;

final class ContactTagTest extends TestCase
{
    public function testDefaults(): void
    {
        $contactTag = new ContactTag();

        self::assertNull($contactTag->getId());
        self::assertSame('', $contactTag->getLabel());
        self::assertSame('', $contactTag->getSlug());
        self::assertSame('#6366F1', $contactTag->getColor());
    }

    public function testSettersAndGetters(): void
    {
        $contactTag = new ContactTag();
        $contactTag->setLabel('VIP');
        $contactTag->setSlug('vip');
        $contactTag->setColor('#FF5733');

        self::assertSame('VIP', $contactTag->getLabel());
        self::assertSame('vip', $contactTag->getSlug());
        self::assertSame('#FF5733', $contactTag->getColor());
    }

    public function testSettersReturnSelf(): void
    {
        $contactTag = new ContactTag();

        self::assertSame($contactTag, $contactTag->setLabel('Prospect'));
        self::assertSame($contactTag, $contactTag->setSlug('prospect'));
        self::assertSame($contactTag, $contactTag->setColor('#112233'));
    }
}
