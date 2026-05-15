<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentCategory\Dto;

use Aurora\Module\Ged\DocumentCategory\Dto\DocumentCategoryInput;
use PHPUnit\Framework\TestCase;

final class DocumentCategoryInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new DocumentCategoryInput();

        self::assertSame('', $input->getName());
        self::assertNull($input->getDescription());
    }

    public function testConstructorValues(): void
    {
        $input = new DocumentCategoryInput('Legal', 'Legal documents');

        self::assertSame('Legal', $input->getName());
        self::assertSame('Legal documents', $input->getDescription());
    }
}
