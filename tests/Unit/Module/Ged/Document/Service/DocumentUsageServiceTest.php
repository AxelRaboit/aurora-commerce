<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\Document\Service;

use Aurora\Module\Ged\Document\Contract\DocumentUsageProviderInterface;
use Aurora\Module\Ged\Document\Service\DocumentUsageService;
use PHPUnit\Framework\TestCase;

final class DocumentUsageServiceTest extends TestCase
{
    public function testFindUsagesReturnsTotalAndGroups(): void
    {
        $provider = $this->createStub(DocumentUsageProviderInterface::class);
        $provider->method('findUsages')->willReturn([
            ['type' => 'billing.invoice', 'label' => 'INV-1'],
            ['type' => 'billing.invoice', 'label' => 'INV-2'],
            ['type' => 'project.task', 'label' => 'Task A'],
        ]);

        $result = (new DocumentUsageService([$provider]))->findUsages(42);

        self::assertSame(3, $result['total']);
        self::assertCount(2, $result['groups']);
    }

    public function testFindUsagesGroupsByType(): void
    {
        $provider = $this->createStub(DocumentUsageProviderInterface::class);
        $provider->method('findUsages')->willReturn([
            ['type' => 'billing.invoice', 'label' => 'INV-1'],
            ['type' => 'billing.invoice', 'label' => 'INV-2'],
        ]);

        $result = (new DocumentUsageService([$provider]))->findUsages(1);

        self::assertCount(1, $result['groups']);
        self::assertSame('billing.invoice', $result['groups'][0]['type']);
        self::assertCount(2, $result['groups'][0]['items']);
    }

    public function testFindUsagesWithNoProviders(): void
    {
        $result = (new DocumentUsageService([]))->findUsages(1);

        self::assertSame(['total' => 0, 'groups' => []], $result);
    }

    public function testFindUsagesAggregatesAcrossProviders(): void
    {
        $invoiceProvider = $this->createStub(DocumentUsageProviderInterface::class);
        $invoiceProvider->method('findUsages')->willReturn([['type' => 'billing.invoice', 'label' => 'INV-1']]);

        $taskProvider = $this->createStub(DocumentUsageProviderInterface::class);
        $taskProvider->method('findUsages')->willReturn([['type' => 'project.task', 'label' => 'Task A']]);

        $result = (new DocumentUsageService([$invoiceProvider, $taskProvider]))->findUsages(1);

        self::assertSame(2, $result['total']);
        self::assertCount(2, $result['groups']);
    }
}
