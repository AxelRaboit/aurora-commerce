<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\PostIt\View;

use Aurora\Module\Notes\PostIt\View\PostItNotesViewBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class PostItNotesViewBuilderTest extends TestCase
{
    public function testExposesAllFiveApiPaths(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $params = []): string => '/stub/'.$route.(isset($params['id']) ? '/'.$params['id'] : ''),
        );

        $view = (new PostItNotesViewBuilder($urlGenerator))->indexView();

        // Five paths drive the Vue board: list (GET), create (POST), and three
        // id-templated routes (update / move / resize / delete) where the
        // placeholder `__id__` is swapped client-side per note.
        self::assertArrayHasKey('listPath', $view);
        self::assertArrayHasKey('createPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('movePath', $view);
        self::assertArrayHasKey('resizePath', $view);
        self::assertArrayHasKey('deletePath', $view);
    }

    public function testIdTemplatedPathsCarryTheIdPlaceholder(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $params = []): string => '/stub/'.$route.(isset($params['id']) ? '/'.$params['id'] : ''),
        );

        $view = (new PostItNotesViewBuilder($urlGenerator))->indexView();

        // The client expects to substitute `__id__` at call time — if the
        // placeholder is dropped or renamed, the front breaks silently.
        self::assertStringContainsString('__id__', $view['updatePath']);
        self::assertStringContainsString('__id__', $view['movePath']);
        self::assertStringContainsString('__id__', $view['resizePath']);
        self::assertStringContainsString('__id__', $view['deletePath']);
    }

    public function testStaticPathsHaveNoIdPlaceholder(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $params = []): string => '/stub/'.$route.(isset($params['id']) ? '/'.$params['id'] : ''),
        );

        $view = (new PostItNotesViewBuilder($urlGenerator))->indexView();

        self::assertStringNotContainsString('__id__', $view['listPath']);
        self::assertStringNotContainsString('__id__', $view['createPath']);
    }
}
