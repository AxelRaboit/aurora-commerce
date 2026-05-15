<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Locale\Service;

use Aurora\Core\Locale\Service\LocaleContext;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class LocaleContextTest extends TestCase
{
    public function testReturnsEnabledLocalesWhenSingleModeIsOff(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('getBoolean')->willReturn(false);
        $repository->method('getOrDefault')->willReturn('fr');

        $context = new LocaleContext($repository, ['fr', 'en']);

        self::assertFalse($context->isSingleLocaleMode());
        self::assertSame(['fr', 'en'], $context->getActiveLocales());
        self::assertSame(['fr', 'en'], $context->getAllLocales());
    }

    public function testReturnsDefaultLocaleOnlyWhenSingleModeIsOn(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('getBoolean')
            ->with(ApplicationParameterEnum::SingleLocaleMode->value, false)
            ->willReturn(true);
        $repository->method('getOrDefault')
            ->with(ApplicationParameterEnum::DefaultLocale)
            ->willReturn('en');

        $context = new LocaleContext($repository, ['fr', 'en']);

        self::assertTrue($context->isSingleLocaleMode());
        self::assertSame('en', $context->getDefaultLocale());
        self::assertSame(['en'], $context->getActiveLocales());
        self::assertSame(['fr', 'en'], $context->getAllLocales(), 'getAllLocales must ignore single-mode toggle');
    }

    public function testFallsBackToBundleDefaultWhenStoredLocaleIsInvalid(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('getBoolean')->willReturn(true);
        $repository->method('getOrDefault')->willReturn('xx');

        $context = new LocaleContext($repository, ['fr', 'en']);

        self::assertSame('fr', $context->getDefaultLocale());
        self::assertSame(['fr'], $context->getActiveLocales());
    }

    public function testMemoizesRepositoryReads(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->expects(self::once())->method('getBoolean')->willReturn(true);
        $repository->expects(self::once())->method('getOrDefault')->willReturn('fr');

        $context = new LocaleContext($repository, ['fr', 'en']);

        $context->isSingleLocaleMode();
        $context->isSingleLocaleMode();
        $context->getDefaultLocale();
        $context->getDefaultLocale();
        $context->getActiveLocales();
    }
}
