<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Markdown\View;

use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Notes\Markdown\Repository\MarkdownNoteRepository;
use Aurora\Module\Notes\Markdown\Setting\MarkdownNoteSettingEnum;
use Aurora\Module\Notes\Markdown\View\MarkdownNotesViewBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class MarkdownNotesViewBuilderTest extends TestCase
{
    public function testImageSettingsFallBackToEnumDefaultsWhenDbHasNoOverride(): void
    {
        $viewBuilder = $this->makeViewBuilder(maxEdgeOverride: null, qualityOverride: null);

        $view = $viewBuilder->indexView(new User());

        self::assertSame(2048, $view['imageMaxEdge']);
        self::assertSame(0.85, $view['imageQuality']);
    }

    public function testImageSettingsReflectDbOverrides(): void
    {
        $viewBuilder = $this->makeViewBuilder(maxEdgeOverride: '1600', qualityOverride: '70');

        $view = $viewBuilder->indexView(new User());

        self::assertSame(1600, $view['imageMaxEdge']);
        self::assertSame(0.70, $view['imageQuality']);
    }

    public function testImageQualityClampsToZeroToOneRange(): void
    {
        $viewBuilder = $this->makeViewBuilder(maxEdgeOverride: '2048', qualityOverride: '250');

        $view = $viewBuilder->indexView(new User());

        // 250% → clamped to 1.0 so the WebP encoder never sees a value
        // outside its accepted [0..1] range.
        self::assertSame(1.0, $view['imageQuality']);
    }

    private function makeViewBuilder(?string $maxEdgeOverride, ?string $qualityOverride): MarkdownNotesViewBuilder
    {
        $noteRepository = $this->createMock(MarkdownNoteRepository::class);
        $noteRepository->method('findFlatListForUser')->willReturn([]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/stub');

        $settingRepository = $this->createMock(SettingRepository::class);
        $settingRepository->method('getOrDefault')->willReturnCallback(
            static fn (MarkdownNoteSettingEnum $parameter): string => match ($parameter) {
                MarkdownNoteSettingEnum::ImageMaxEdge => $maxEdgeOverride ?? $parameter->getDefaultValue(),
                MarkdownNoteSettingEnum::ImageQualityPct => $qualityOverride ?? $parameter->getDefaultValue(),
            },
        );

        return new MarkdownNotesViewBuilder($noteRepository, $urlGenerator, $settingRepository);
    }
}
