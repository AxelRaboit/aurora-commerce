<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Menu\View;

use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Repository\MenuRepository;
use Aurora\Core\Menu\Serializer\MenuSerializerInterface;
use Aurora\Core\Menu\View\MenusViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MenusViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsAllSections(): void
    {
        $repo = $this->createStub(MenuRepository::class);
        $repo->method('findAllForIndex')->willReturn([new Menu(), new Menu()]);

        $serializer = $this->createStub(MenuSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $view = (new MenusViewBuilder($repo, $serializer, $translator))->indexView(['fr', 'en']);

        self::assertCount(2, $view['menus']);
        self::assertSame(['fr', 'en'], $view['locales']);
        self::assertNotEmpty($view['targetTypes']);
        self::assertNotEmpty($view['visibilities']);
    }

    public function testIndexViewTargetTypesHaveValueAndLabel(): void
    {
        $repo = $this->createStub(MenuRepository::class);
        $repo->method('findAllForIndex')->willReturn([]);

        $serializer = $this->createStub(MenuSerializerInterface::class);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $view = (new MenusViewBuilder($repo, $serializer, $translator))->indexView([]);

        foreach ($view['targetTypes'] as $type) {
            self::assertArrayHasKey('value', $type);
            self::assertArrayHasKey('label', $type);
        }
    }
}
