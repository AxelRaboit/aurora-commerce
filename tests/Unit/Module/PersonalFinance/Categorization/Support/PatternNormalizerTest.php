<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PersonalFinance\Categorization\Support;

use Aurora\Module\PersonalFinance\Categorization\Support\PatternNormalizer;
use PHPUnit\Framework\TestCase;

final class PatternNormalizerTest extends TestCase
{
    public function testStripsAccentsViaAsciiTranslit(): void
    {
        self::assertSame('cafe', PatternNormalizer::normalize('Café'));
        self::assertSame('boulangerie lena', PatternNormalizer::normalize('Boulangerie Léna'));
        self::assertSame('hotel etoile', PatternNormalizer::normalize('Hôtel Étoile'));
    }

    public function testLowercasesInput(): void
    {
        self::assertSame('uppercase', PatternNormalizer::normalize('UPPERCASE'));
        self::assertSame('mixedcase', PatternNormalizer::normalize('MiXeDcAsE'));
    }

    public function testCollapsesWhitespace(): void
    {
        self::assertSame('multiple spaces', PatternNormalizer::normalize('  multiple   spaces  '));
        self::assertSame('tab and newline', PatternNormalizer::normalize("tab\tand\nnewline"));
    }

    public function testNullInputReturnsNull(): void
    {
        self::assertNull(PatternNormalizer::normalize(null));
    }

    public function testEmptyInputReturnsNull(): void
    {
        self::assertNull(PatternNormalizer::normalize(''));
    }

    public function testWhitespaceOnlyInputReturnsNull(): void
    {
        self::assertNull(PatternNormalizer::normalize('   '));
        self::assertNull(PatternNormalizer::normalize("\t\n\r"));
    }

    public function testCombinedNormalisation(): void
    {
        self::assertSame('cafe & the', PatternNormalizer::normalize('Cafe & THÉ'));
        self::assertSame('cafe & the', PatternNormalizer::normalize('  CAFÉ  &   thé  '));
    }
}
