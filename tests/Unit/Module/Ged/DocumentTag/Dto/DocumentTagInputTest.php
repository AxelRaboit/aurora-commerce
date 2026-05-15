<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentTag\Dto;

use Aurora\Module\Ged\DocumentTag\Dto\DocumentTagInput;
use PHPUnit\Framework\TestCase;

final class DocumentTagInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new DocumentTagInput();

        self::assertSame('', $input->getName());
        self::assertNull($input->getColor());
    }

    public function testConstructorValues(): void
    {
        $input = new DocumentTagInput('Important', '#ff0000');

        self::assertSame('Important', $input->getName());
        self::assertSame('#ff0000', $input->getColor());
    }
}
