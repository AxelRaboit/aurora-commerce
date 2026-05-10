<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\View;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Setting\View\ModulesViewBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class ModulesViewBuilderTest extends TestCase
{
    private function makeBuilder(
        SettingRepository $repository,
        TranslatorInterface $translator,
        iterable $modules = [],
    ): ModulesViewBuilder {
        return new ModulesViewBuilder($repository, $modules, $translator);
    }

    private function stubRepository(string $defaultValue = '1'): SettingRepository
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('get')->willReturn($defaultValue);

        return $repository;
    }

    private function stubTranslator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => $id);

        return $translator;
    }

    public function testModulesPayloadReturnsParametersKey(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator());
        $payload = $builder->modulesPayload();

        self::assertArrayHasKey('parameters', $payload);
        self::assertIsArray($payload['parameters']);
    }

    public function testModulesPayloadContainsOnlyTopLevelParameters(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator());
        $payload = $builder->modulesPayload();

        $topLevelCount = 0;
        foreach (ModuleParameterEnum::cases() as $case) {
            if (null === $case->getParentCase()) {
                ++$topLevelCount;
            }
        }

        self::assertCount($topLevelCount, $payload['parameters']);
    }

    public function testEachTopLevelParameterHasRequiredKeys(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator());
        $payload = $builder->modulesPayload();

        foreach ($payload['parameters'] as $parameter) {
            self::assertArrayHasKey('key', $parameter);
            self::assertArrayHasKey('label', $parameter);
            self::assertArrayHasKey('description', $parameter);
            self::assertArrayHasKey('value', $parameter);
            self::assertArrayHasKey('requires', $parameter);
            self::assertArrayHasKey('navItems', $parameter);
            self::assertArrayHasKey('subModules', $parameter);
        }
    }

    public function testSubModulesAreAttachedToCorrectParent(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator());
        $payload = $builder->modulesPayload();

        $billingParam = null;
        foreach ($payload['parameters'] as $parameter) {
            if ($parameter['key'] === ModuleParameterEnum::BillingEnabled->value) {
                $billingParam = $parameter;
                break;
            }
        }

        self::assertNotNull($billingParam);
        self::assertIsArray($billingParam['subModules']);

        $subKeys = array_column($billingParam['subModules'], 'key');
        self::assertContains(ModuleParameterEnum::BillingTiersEnabled->value, $subKeys);
        self::assertContains(ModuleParameterEnum::BillingInvoicesEnabled->value, $subKeys);
        self::assertContains(ModuleParameterEnum::BillingComplianceEnabled->value, $subKeys);
    }

    public function testEachSubModuleHasRequiredKeys(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator());
        $payload = $builder->modulesPayload();

        foreach ($payload['parameters'] as $parameter) {
            foreach ($parameter['subModules'] as $subModule) {
                self::assertArrayHasKey('key', $subModule);
                self::assertArrayHasKey('label', $subModule);
                self::assertArrayHasKey('description', $subModule);
                self::assertArrayHasKey('value', $subModule);
                self::assertArrayHasKey('requires', $subModule);
            }
        }
    }

    public function testSubModuleRequiresReflectsCascadeRequires(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator());
        $payload = $builder->modulesPayload();

        $billingParam = null;
        foreach ($payload['parameters'] as $parameter) {
            if ($parameter['key'] === ModuleParameterEnum::BillingEnabled->value) {
                $billingParam = $parameter;
                break;
            }
        }

        self::assertNotNull($billingParam);

        $invoicesSub = null;
        foreach ($billingParam['subModules'] as $sub) {
            if ($sub['key'] === ModuleParameterEnum::BillingInvoicesEnabled->value) {
                $invoicesSub = $sub;
                break;
            }
        }

        self::assertNotNull($invoicesSub);
        self::assertSame(ModuleParameterEnum::BillingTiersEnabled->value, $invoicesSub['requires']);
    }

    public function testIndexViewReturnsTabbedStructure(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator());
        $payload = ['parameters' => []];
        $view = $builder->indexView($payload);

        self::assertSame('modules', $view['tab']);
        self::assertSame($payload, $view['modules']);
    }

    public function testNavItemsEmptyWhenNoModulesRegistered(): void
    {
        $builder = $this->makeBuilder($this->stubRepository(), $this->stubTranslator(), []);
        $payload = $builder->modulesPayload();

        foreach ($payload['parameters'] as $parameter) {
            self::assertSame([], $parameter['navItems']);
        }
    }
}
