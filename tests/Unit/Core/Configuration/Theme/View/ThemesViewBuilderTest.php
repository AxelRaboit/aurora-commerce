<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Configuration\Theme\View;

use Aurora\Core\Configuration\Theme\Entity\Theme;
use Aurora\Core\Configuration\Theme\Repository\ThemeRepository;
use Aurora\Core\Configuration\Theme\Serializer\ThemeSerializerInterface;
use Aurora\Core\Configuration\Theme\View\ThemesViewBuilder;
use PHPUnit\Framework\TestCase;

final class ThemesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsSerializedThemes(): void
    {
        $repository = $this->createStub(ThemeRepository::class);
        $repository->method('findAll')->willReturn([new Theme(), new Theme(), new Theme()]);

        $serializer = $this->createStub(ThemeSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['slug' => 'dark']);

        $view = (new ThemesViewBuilder($repository, $serializer))->indexView();

        self::assertArrayHasKey('themes', $view);
        self::assertCount(3, $view['themes']);
    }

    public function testIndexViewWithEmptyRepository(): void
    {
        $repository = $this->createStub(ThemeRepository::class);
        $repository->method('findAll')->willReturn([]);

        $serializer = $this->createStub(ThemeSerializerInterface::class);

        $view = (new ThemesViewBuilder($repository, $serializer))->indexView();

        self::assertSame(['themes' => []], $view);
    }
}
