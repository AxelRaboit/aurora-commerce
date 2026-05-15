<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentCategory\Dto;

use Aurora\Module\Ged\DocumentCategory\Dto\DocumentCategoryInputFactory;
use PHPUnit\Framework\TestCase;

final class DocumentCategoryInputFactoryTest extends TestCase
{
    public function testFromArrayParsesFields(): void
    {
        $input = (new DocumentCategoryInputFactory())->fromArray([
            'name' => '  Legal  ',
            'description' => '  Description  ',
        ]);

        self::assertSame('Legal', $input->getName());
        self::assertSame('Description', $input->getDescription());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new DocumentCategoryInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertNull($input->getDescription());
    }
}
