<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Trait;

use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use PHPUnit\Framework\TestCase;

final class ScalarCoercionTraitTest extends TestCase
{
    /** @var object{stringOrNull: callable, intOrNull: callable, intOrNullSafe: callable, dateOrNull: callable, dateOrNullSafe: callable} */
    private object $sut;

    protected function setUp(): void
    {
        // Anonymous class exposing the protected helpers as public callables for the test.
        $this->sut = new class {
            use ScalarCoercionTrait {
                stringOrNull as public;
                intOrNull as public;
                intOrNullSafe as public;
                dateOrNull as public;
                dateOrNullSafe as public;
            }
        };
    }

    public function testStringOrNullTrimsAndDropsEmpty(): void
    {
        self::assertSame('hello', $this->sut->stringOrNull('  hello '));
        self::assertNull($this->sut->stringOrNull('   '));
        self::assertNull($this->sut->stringOrNull(''));
        self::assertNull($this->sut->stringOrNull(null));
    }

    public function testStringOrNullRejectsNonScalar(): void
    {
        self::assertNull($this->sut->stringOrNull(['a']));
        self::assertNull($this->sut->stringOrNull(new \stdClass()));
    }

    public function testStringOrNullPreservesUnicodeWhitespace(): void
    {
        // mb_trim should strip unicode whitespace too (NBSP, em space, ideographic).
        self::assertSame('foo', $this->sut->stringOrNull("\u{00A0}foo\u{2003}"));
    }

    public function testIntOrNullStrictParsing(): void
    {
        self::assertSame(42, $this->sut->intOrNull(42));
        self::assertSame(42, $this->sut->intOrNull('42'));
        self::assertSame(42, $this->sut->intOrNull('42.7'));
        self::assertNull($this->sut->intOrNull(null));
        self::assertNull($this->sut->intOrNull(''));
    }

    public function testIntOrNullThrowsOnNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('shared.validation.notNumeric');
        $this->sut->intOrNull('abc');
    }

    public function testIntOrNullSafeReturnsNullOnGarbage(): void
    {
        self::assertSame(7, $this->sut->intOrNullSafe(7));
        self::assertSame(7, $this->sut->intOrNullSafe('7'));
        self::assertNull($this->sut->intOrNullSafe('abc'));
        self::assertNull($this->sut->intOrNullSafe(null));
    }

    public function testDateOrNullParsesIso(): void
    {
        $d = $this->sut->dateOrNull('2025-12-31');
        self::assertInstanceOf(\DateTimeImmutable::class, $d);
        self::assertSame('2025-12-31', $d->format('Y-m-d'));
        self::assertNull($this->sut->dateOrNull(null));
        self::assertNull($this->sut->dateOrNull(''));
    }

    public function testDateOrNullThrowsOnGarbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('shared.validation.invalidDate');
        $this->sut->dateOrNull('not-a-date');
    }

    public function testDateOrNullSafeReturnsNullOnGarbage(): void
    {
        self::assertNull($this->sut->dateOrNullSafe('not-a-date'));
        self::assertNull($this->sut->dateOrNullSafe(123));
        self::assertInstanceOf(\DateTimeImmutable::class, $this->sut->dateOrNullSafe('2025-12-31'));
    }
}
