<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Agency\View;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Agency\Serializer\AgencySerializerInterface;
use Aurora\Core\Agency\View\AgenciesViewBuilder;
use PHPUnit\Framework\TestCase;

final class AgenciesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsSerializedAgencies(): void
    {
        $repository = $this->createStub(AgencyRepository::class);
        $repository->method('findAllAlphabetical')->willReturn([new Agency(), new Agency()]);

        $serializer = $this->createStub(AgencySerializerInterface::class);
        $serializer->method('serialize')->willReturn(['name' => 'X']);

        $view = (new AgenciesViewBuilder($repository, $serializer))->indexView();

        self::assertArrayHasKey('agencies', $view);
        self::assertCount(2, $view['agencies']);
    }

    public function testIndexViewWithEmptyRepository(): void
    {
        $repository = $this->createStub(AgencyRepository::class);
        $repository->method('findAllAlphabetical')->willReturn([]);

        $serializer = $this->createStub(AgencySerializerInterface::class);

        $view = (new AgenciesViewBuilder($repository, $serializer))->indexView();

        self::assertSame(['agencies' => []], $view);
    }
}
