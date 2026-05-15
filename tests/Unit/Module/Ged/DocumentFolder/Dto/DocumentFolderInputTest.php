<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentFolder\Dto;

use Aurora\Module\Ged\DocumentFolder\Dto\DocumentFolderInput;
use PHPUnit\Framework\TestCase;

final class DocumentFolderInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new DocumentFolderInput();

        self::assertSame('', $input->getName());
        self::assertNull($input->getParentId());
    }

    public function testConstructorValues(): void
    {
        $input = new DocumentFolderInput('Contracts', 42);

        self::assertSame('Contracts', $input->getName());
        self::assertSame(42, $input->getParentId());
    }
}
