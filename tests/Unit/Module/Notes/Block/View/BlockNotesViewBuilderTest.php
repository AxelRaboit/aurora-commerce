<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Block\View;

use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Notes\Block\Repository\BlockNoteRepository;
use Aurora\Module\Notes\Block\Setting\BlockNoteSettingEnum;
use Aurora\Module\Notes\Block\View\BlockNotesViewBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class BlockNotesViewBuilderTest extends TestCase
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

    private function makeViewBuilder(?string $maxEdgeOverride, ?string $qualityOverride): BlockNotesViewBuilder
    {
        $noteRepository = $this->createMock(BlockNoteRepository::class);
        $noteRepository->method('findFlatListForUser')->willReturn([]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/stub');

        $settingRepository = $this->createMock(SettingRepository::class);
        $settingRepository->method('getOrDefault')->willReturnCallback(
            static fn (BlockNoteSettingEnum $parameter): string => match ($parameter) {
                BlockNoteSettingEnum::ImageMaxEdge => $maxEdgeOverride ?? $parameter->getDefaultValue(),
                BlockNoteSettingEnum::ImageQualityPct => $qualityOverride ?? $parameter->getDefaultValue(),
            },
        );

        return new BlockNotesViewBuilder($noteRepository, $urlGenerator, $settingRepository);
    }
}
