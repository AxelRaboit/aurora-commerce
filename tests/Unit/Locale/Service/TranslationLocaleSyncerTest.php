<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Locale\Service;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Locale\Service\TranslationLocaleSyncer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class TranslationLocaleSyncerTest extends TestCase
{
    private function makeContext(array $active): LocaleContextInterface
    {
        $context = $this->createMock(LocaleContextInterface::class);
        $context->method('getActiveLocales')->willReturn($active);

        return $context;
    }

    public function testMarksActiveLocalesMissingFromInputAsStale(): void
    {
        $syncer = new TranslationLocaleSyncer($this->makeContext(LocaleEnum::values()));

        $existing = [LocaleEnum::French->value => LocaleEnum::French, LocaleEnum::English->value => LocaleEnum::English];
        $stale = $syncer->stale($existing, [LocaleEnum::French->value]);

        self::assertSame([LocaleEnum::English], $stale);
    }

    public function testPreservesNonActiveLocalesEvenWhenAbsentFromInput(): void
    {
        $syncer = new TranslationLocaleSyncer($this->makeContext([LocaleEnum::French->value]));

        $existing = [LocaleEnum::French->value => LocaleEnum::French, LocaleEnum::English->value => LocaleEnum::English];
        $stale = $syncer->stale($existing, [LocaleEnum::French->value]);

        self::assertSame([], $stale, 'EN translation must survive when EN is not in active locales');
    }

    public function testReturnsEmptyWhenAllActiveLocalesArePresent(): void
    {
        $syncer = new TranslationLocaleSyncer($this->makeContext(LocaleEnum::values()));

        $existing = [LocaleEnum::French->value => LocaleEnum::French, LocaleEnum::English->value => LocaleEnum::English];
        $stale = $syncer->stale($existing, LocaleEnum::values());

        self::assertSame([], $stale);
    }

    public function testHandlesEmptyInputLocales(): void
    {
        $syncer = new TranslationLocaleSyncer($this->makeContext(LocaleEnum::values()));

        $existing = [LocaleEnum::French->value => LocaleEnum::French, LocaleEnum::English->value => LocaleEnum::English];
        $stale = $syncer->stale($existing, []);

        self::assertSame([LocaleEnum::French, LocaleEnum::English], $stale);
    }
}
