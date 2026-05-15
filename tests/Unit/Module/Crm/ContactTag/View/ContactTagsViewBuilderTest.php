<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\ContactTag\View;

use Aurora\Module\Crm\ContactTag\View\ContactTagsViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ContactTagsViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsAllUrlsAndTags(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $tags = [['id' => 1, 'label' => 'VIP']];

        $view = (new ContactTagsViewBuilder($urlGenerator))->indexView($tags);

        self::assertSame($tags, $view['tags']);
        self::assertArrayHasKey('listPath', $view);
        self::assertArrayHasKey('createPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('deletePath', $view);
    }
}
