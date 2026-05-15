<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Locale\Enum;

use Aurora\Core\Locale\Enum\CountryEnum;
use PHPUnit\Framework\TestCase;

final class CountryEnumTest extends TestCase
{
    public function testValuesReturnsAllIsoCodes(): void
    {
        $values = CountryEnum::values();

        self::assertContains('FR', $values);
        self::assertContains('BE', $values);
        self::assertContains('CH', $values);
        self::assertContains('US', $values);
        self::assertCount(8, $values);
    }

    public function testDefaultIsFrance(): void
    {
        self::assertSame(CountryEnum::France, CountryEnum::default());
    }

    public function testLabelReturnsLocalizedName(): void
    {
        self::assertSame('France', CountryEnum::France->label('fr'));
        self::assertSame('France', CountryEnum::France->label('en'));
    }

    public function testOptionsReturnsCodeToLabelMap(): void
    {
        $options = CountryEnum::options('fr');

        self::assertArrayHasKey('FR', $options);
        self::assertArrayHasKey('BE', $options);
        self::assertCount(8, $options);
        self::assertSame('France', $options['FR']);
    }

    public function testOptionsSortedAlphabeticallyByLabel(): void
    {
        $labels = array_values(CountryEnum::options('fr'));
        $sorted = $labels;
        sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);

        self::assertSame($sorted, $labels, 'options must be sorted by label in given locale');
    }
}
