<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Service\View;

use Aurora\Module\Platform\Service\Entity\Service;
use Aurora\Module\Platform\Service\Repository\ServiceRepository;
use Aurora\Module\Platform\Service\Serializer\ServiceSerializerInterface;
use Aurora\Module\Platform\Service\View\ServicesViewBuilder;
use PHPUnit\Framework\TestCase;

final class ServicesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsSerializedServices(): void
    {
        $service1 = new Service();
        $service2 = new Service();

        $repository = $this->createStub(ServiceRepository::class);
        $repository->method('findAllAlphabetical')->willReturn([$service1, $service2]);

        $serializer = $this->createStub(ServiceSerializerInterface::class);
        $serializer->method('serialize')->willReturnCallback(
            static fn (Service $s): array => ['id' => spl_object_id($s)],
        );

        $builder = new ServicesViewBuilder($repository, $serializer);
        $view = $builder->indexView();

        self::assertArrayHasKey('services', $view);
        self::assertCount(2, $view['services']);
    }

    public function testIndexViewWithEmptyRepositoryReturnsEmptyArray(): void
    {
        $repository = $this->createStub(ServiceRepository::class);
        $repository->method('findAllAlphabetical')->willReturn([]);

        $serializer = $this->createStub(ServiceSerializerInterface::class);

        $view = (new ServicesViewBuilder($repository, $serializer))->indexView();

        self::assertSame(['services' => []], $view);
    }
}
