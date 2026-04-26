<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Module\Editorial\Post\Entity\PostTypeField;
use PHPUnit\Framework\TestCase;

final class PostTypeFieldTest extends TestCase
{
    public function testReferenceIsSupportedAsFieldType(): void
    {
        self::assertContains('reference', PostTypeField::TYPES);
    }
}
