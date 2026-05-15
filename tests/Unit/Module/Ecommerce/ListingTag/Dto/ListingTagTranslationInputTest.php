<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\ListingTag\Dto;

use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagTranslationInput;
use PHPUnit\Framework\TestCase;

final class ListingTagTranslationInputTest extends TestCase
{
    public function testFromArrayTrimsName(): void
    {
        $input = ListingTagTranslationInput::fromArray(['name' => '  Promo  ']);

        self::assertSame('Promo', $input->name);
    }

    public function testFromArrayTrimsOrNullsOptionalFields(): void
    {
        $input = ListingTagTranslationInput::fromArray([
            'name' => 'Promo',
            'slug' => '  promo  ',
            'description' => '  Description  ',
        ]);

        self::assertSame('promo', $input->slug);
        self::assertSame('Description', $input->description);
    }

    public function testFromArrayWithMissingOptionalsReturnsNull(): void
    {
        $input = ListingTagTranslationInput::fromArray(['name' => 'Tag']);

        self::assertNull($input->slug);
        self::assertNull($input->description);
    }
}
