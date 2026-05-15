<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Taxonomy\Dto;

use Aurora\Module\Editorial\Taxonomy\Dto\TaxonomyTermInput;
use PHPUnit\Framework\TestCase;

final class TaxonomyTermInputTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $translations = [
            'fr' => ['name' => 'Actualités', 'slug' => 'actualites'],
            'en' => ['name' => 'News', 'slug' => 'news'],
        ];

        $input = new TaxonomyTermInput($translations, 42);

        self::assertSame($translations, $input->getTranslations());
        self::assertSame(42, $input->getParentId());
    }

    public function testParentIdDefaultsToNull(): void
    {
        $input = new TaxonomyTermInput(['fr' => ['name' => 'Test', 'slug' => 'test']]);

        self::assertNull($input->getParentId());
    }
}
