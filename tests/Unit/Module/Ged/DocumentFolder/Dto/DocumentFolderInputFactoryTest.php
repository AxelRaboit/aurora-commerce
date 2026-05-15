<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentFolder\Dto;

use Aurora\Module\Ged\DocumentFolder\Dto\DocumentFolderInputFactory;
use PHPUnit\Framework\TestCase;

final class DocumentFolderInputFactoryTest extends TestCase
{
    public function testFromArrayParsesFields(): void
    {
        $input = (new DocumentFolderInputFactory())->fromArray([
            'name' => '  Contracts  ',
            'parentId' => 42,
        ]);

        self::assertSame('Contracts', $input->getName());
        self::assertSame(42, $input->getParentId());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new DocumentFolderInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertNull($input->getParentId());
    }

    public function testFromArrayNullsEmptyParentId(): void
    {
        $input = (new DocumentFolderInputFactory())->fromArray(['parentId' => 0]);

        self::assertNull($input->getParentId());
    }
}
