<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Support;

use Aurora\Core\Support\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testTrimOrNullReturnsTrimmedString(): void
    {
        self::assertSame('hello', Str::trimOrNull('  hello  '));
    }

    public function testTrimOrNullReturnsNullForEmptyOrWhitespace(): void
    {
        self::assertNull(Str::trimOrNull(''));
        self::assertNull(Str::trimOrNull('   '));
    }

    public function testTrimOrNullFromArrayReturnsTrimmedValue(): void
    {
        self::assertSame('world', Str::trimOrNullFromArray(['name' => '  world  '], 'name'));
    }

    public function testTrimOrNullFromArrayReturnsNullForMissingKey(): void
    {
        self::assertNull(Str::trimOrNullFromArray([], 'name'));
    }

    public function testTrimOrNullFromArrayReturnsNullForEmptyString(): void
    {
        self::assertNull(Str::trimOrNullFromArray(['name' => '   '], 'name'));
    }

    public function testTrimFromArrayReturnsTrimmedValue(): void
    {
        self::assertSame('Aurora', Str::trimFromArray(['title' => '  Aurora  '], 'title'));
    }

    public function testTrimFromArrayReturnsDefaultWhenAbsent(): void
    {
        self::assertSame('', Str::trimFromArray([], 'title'));
        self::assertSame('default', Str::trimFromArray([], 'title', 'default'));
    }

    public function testEmailFromArrayLowercasesAndTrims(): void
    {
        self::assertSame('jane@example.com', Str::emailFromArray(['email' => '  Jane@Example.com  '], 'email'));
    }

    public function testEmailFromArrayReturnsEmptyForMissingKey(): void
    {
        self::assertSame('', Str::emailFromArray([], 'email'));
    }

    public function testEmailOrNullFromArrayLowercasesAndTrims(): void
    {
        self::assertSame('jane@example.com', Str::emailOrNullFromArray(['email' => '  Jane@Example.com  '], 'email'));
    }

    public function testEmailOrNullFromArrayReturnsNullForMissingOrEmpty(): void
    {
        self::assertNull(Str::emailOrNullFromArray([], 'email'));
        self::assertNull(Str::emailOrNullFromArray(['email' => '   '], 'email'));
    }
}
