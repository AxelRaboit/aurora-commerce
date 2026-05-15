<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Planning\View;

use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Repository\PlanningRepository;
use Aurora\Module\Planning\Planning\Serializer\PlanningSerializerInterface;
use Aurora\Module\Planning\Planning\View\PlanningsViewBuilder;
use PHPUnit\Framework\TestCase;

final class PlanningsViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsSerializedPlannings(): void
    {
        $repository = $this->createStub(PlanningRepository::class);
        $repository->method('findAllOrderedByName')->willReturn([new Planning(), new Planning()]);

        $serializer = $this->createStub(PlanningSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $view = (new PlanningsViewBuilder($repository, $serializer))->indexView();

        self::assertArrayHasKey('plannings', $view);
        self::assertCount(2, $view['plannings']);
    }

    public function testIndexViewWithEmptyRepository(): void
    {
        $repository = $this->createStub(PlanningRepository::class);
        $repository->method('findAllOrderedByName')->willReturn([]);

        $serializer = $this->createStub(PlanningSerializerInterface::class);

        $view = (new PlanningsViewBuilder($repository, $serializer))->indexView();

        self::assertSame(['plannings' => []], $view);
    }
}
