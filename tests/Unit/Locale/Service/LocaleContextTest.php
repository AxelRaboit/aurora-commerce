<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Locale\Service;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Locale\Service\LocaleContext;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class LocaleContextTest extends TestCase
{
    public function testReturnsEnabledLocalesWhenSingleModeIsOff(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('getBoolean')->willReturn(false);
        $repository->method('getOrDefault')->willReturn(LocaleEnum::French->value);

        $context = new LocaleContext($repository, LocaleEnum::values());

        self::assertFalse($context->isSingleLocaleMode());
        self::assertSame(LocaleEnum::values(), $context->getActiveLocales());
        self::assertSame(LocaleEnum::values(), $context->getAllLocales());
    }

    public function testReturnsDefaultLocaleOnlyWhenSingleModeIsOn(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('getBoolean')->willReturn(true);
        $repository->method('getOrDefault')->willReturn(LocaleEnum::English->value);

        $context = new LocaleContext($repository, LocaleEnum::values());

        self::assertTrue($context->isSingleLocaleMode());
        self::assertSame(LocaleEnum::English->value, $context->getDefaultLocale());
        self::assertSame([LocaleEnum::English->value], $context->getActiveLocales());
        self::assertSame(LocaleEnum::values(), $context->getAllLocales(), 'getAllLocales must ignore single-mode toggle');
    }

    public function testFallsBackToBundleDefaultWhenStoredLocaleIsInvalid(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('getBoolean')->willReturn(true);
        $repository->method('getOrDefault')->willReturn('xx');

        $context = new LocaleContext($repository, LocaleEnum::values());

        self::assertSame(LocaleEnum::default()->value, $context->getDefaultLocale());
        self::assertSame([LocaleEnum::default()->value], $context->getActiveLocales());
    }

    public function testMemoizesRepositoryReads(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->expects(self::once())->method('getBoolean')->willReturn(true);
        $repository->expects(self::once())->method('getOrDefault')->willReturn(LocaleEnum::French->value);

        $context = new LocaleContext($repository, LocaleEnum::values());

        $context->isSingleLocaleMode();
        $context->isSingleLocaleMode();
        $context->getDefaultLocale();
        $context->getDefaultLocale();
        $context->getActiveLocales();
    }
}
