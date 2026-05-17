<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Media\Library\Service;

use Aurora\Core\Media\Library\Contract\MediaUsageProviderInterface;
use Aurora\Core\Media\Library\Service\MediaUsageService;
use PHPUnit\Framework\TestCase;

final class MediaUsageServiceTest extends TestCase
{
    public function testFindUsagesReturnsTotalAndGroups(): void
    {
        $provider = $this->createStub(MediaUsageProviderInterface::class);
        $provider->method('findUsages')->willReturn([
            ['type' => 'post', 'id' => 1],
            ['type' => 'post', 'id' => 2],
            ['type' => 'gallery', 'id' => 5],
        ]);

        $service = new MediaUsageService([$provider]);
        $result = $service->findUsages(42);

        self::assertSame(3, $result['total']);
        self::assertCount(2, $result['groups']);
    }

    public function testFindUsagesGroupsByType(): void
    {
        $provider = $this->createStub(MediaUsageProviderInterface::class);
        $provider->method('findUsages')->willReturn([
            ['type' => 'post', 'id' => 1],
            ['type' => 'post', 'id' => 2],
        ]);

        $result = (new MediaUsageService([$provider]))->findUsages(1);

        self::assertCount(1, $result['groups']);
        self::assertSame('post', $result['groups'][0]['type']);
        self::assertCount(2, $result['groups'][0]['items']);
    }

    public function testFindUsagesWithNoProviders(): void
    {
        $result = (new MediaUsageService([]))->findUsages(1);

        self::assertSame(['total' => 0, 'groups' => []], $result);
    }

    public function testFindUsagesAggregatesAcrossProviders(): void
    {
        $provider1 = $this->createStub(MediaUsageProviderInterface::class);
        $provider1->method('findUsages')->willReturn([['type' => 'post', 'id' => 1]]);

        $provider2 = $this->createStub(MediaUsageProviderInterface::class);
        $provider2->method('findUsages')->willReturn([['type' => 'gallery', 'id' => 5]]);

        $result = (new MediaUsageService([$provider1, $provider2]))->findUsages(1);

        self::assertSame(2, $result['total']);
        self::assertCount(2, $result['groups']);
    }
}
