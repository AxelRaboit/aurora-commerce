<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ged\DocumentTag\Entity\DocumentTag;
use PHPUnit\Framework\TestCase;

final class DocumentTagTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new DocumentTag())->getId());
    }

    public function testColorIsNullByDefault(): void
    {
        self::assertNull((new DocumentTag())->getColor());
    }

    public function testNameGetterAndSetter(): void
    {
        $tag = (new DocumentTag())->setName('Important');

        self::assertSame('Important', $tag->getName());
    }

    public function testColorGetterAndSetter(): void
    {
        $tag = (new DocumentTag())->setColor('#ff0000');

        self::assertSame('#ff0000', $tag->getColor());

        $tag->setColor(null);
        self::assertNull($tag->getColor());
    }
}
