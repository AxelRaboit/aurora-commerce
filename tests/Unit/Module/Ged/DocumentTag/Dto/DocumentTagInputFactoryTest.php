<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentTag\Dto;

use Aurora\Module\Ged\DocumentTag\Dto\DocumentTagInputFactory;
use PHPUnit\Framework\TestCase;

final class DocumentTagInputFactoryTest extends TestCase
{
    public function testFromArrayParsesFields(): void
    {
        $input = (new DocumentTagInputFactory())->fromArray([
            'name' => '  Urgent  ',
            'color' => '  #ff0000  ',
        ]);

        self::assertSame('Urgent', $input->getName());
        self::assertSame('#ff0000', $input->getColor());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new DocumentTagInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertNull($input->getColor());
    }
}
