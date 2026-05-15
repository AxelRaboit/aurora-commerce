<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Locale\Service;

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
        $syncer = new TranslationLocaleSyncer($this->makeContext(['fr', 'en']));

        $existing = ['fr' => 'fr-row', 'en' => 'en-row'];
        $stale = $syncer->stale($existing, ['fr']);

        self::assertSame(['en-row'], $stale);
    }

    public function testPreservesNonActiveLocalesEvenWhenAbsentFromInput(): void
    {
        $syncer = new TranslationLocaleSyncer($this->makeContext(['fr']));

        $existing = ['fr' => 'fr-row', 'en' => 'en-row'];
        $stale = $syncer->stale($existing, ['fr']);

        self::assertSame([], $stale, 'EN row must survive when EN is not in active locales');
    }

    public function testReturnsEmptyWhenAllActiveLocalesArePresent(): void
    {
        $syncer = new TranslationLocaleSyncer($this->makeContext(['fr', 'en']));

        $existing = ['fr' => 'fr-row', 'en' => 'en-row'];
        $stale = $syncer->stale($existing, ['fr', 'en']);

        self::assertSame([], $stale);
    }

    public function testHandlesEmptyInputLocales(): void
    {
        $syncer = new TranslationLocaleSyncer($this->makeContext(['fr', 'en']));

        $existing = ['fr' => 'fr-row', 'en' => 'en-row'];
        $stale = $syncer->stale($existing, []);

        self::assertSame(['fr-row', 'en-row'], $stale);
    }
}
