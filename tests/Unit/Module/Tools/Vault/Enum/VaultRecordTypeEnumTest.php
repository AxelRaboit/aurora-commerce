<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Tools\Vault\Enum;

use Aurora\Module\Tools\Vault\Enum\VaultRecordTypeEnum;
use PHPUnit\Framework\TestCase;

final class VaultRecordTypeEnumTest extends TestCase
{
    public function testEachCaseHasUniqueStringValue(): void
    {
        $values = array_map(static fn (VaultRecordTypeEnum $case): string => $case->value, VaultRecordTypeEnum::cases());

        self::assertSame(array_unique($values), $values);
    }

    public function testLabelReturnsTranslationKeyForEachCase(): void
    {
        foreach (VaultRecordTypeEnum::cases() as $case) {
            self::assertStringStartsWith('vault.types.', $case->label());
        }
    }

    public function testIconReturnsNonEmptyStringForEachCase(): void
    {
        foreach (VaultRecordTypeEnum::cases() as $case) {
            self::assertNotEmpty($case->icon());
        }
    }

    public function testLoginLabelAndIcon(): void
    {
        self::assertSame('vault.types.login', VaultRecordTypeEnum::Login->label());
        self::assertSame('key-round', VaultRecordTypeEnum::Login->icon());
    }
}
