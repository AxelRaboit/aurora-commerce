<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Dashboard\View;

use Aurora\Core\Dashboard\Service\StatsService;
use Aurora\Core\Dashboard\View\OverviewViewBuilder;
use PHPUnit\Framework\TestCase;

final class OverviewViewBuilderTest extends TestCase
{
    public function testOverviewPayloadReturnsStats(): void
    {
        $stats = $this->createStub(StatsService::class);
        $stats->method('getStats')->willReturn(['total' => 100]);

        $payload = (new OverviewViewBuilder($stats))->overviewPayload();

        self::assertSame(['stats' => ['total' => 100]], $payload);
    }

    public function testIndexViewWrapsPayload(): void
    {
        $stats = $this->createStub(StatsService::class);
        $builder = new OverviewViewBuilder($stats);

        $view = $builder->indexView(['stats' => ['total' => 50]]);

        self::assertSame('overview', $view['tab']);
        self::assertSame(['total' => 50], $view['stats']);
    }
}
