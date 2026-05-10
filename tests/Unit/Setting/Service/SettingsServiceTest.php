<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\Service;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Exception\CascadeViolationException;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Setting\Service\SettingsService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class SettingsServiceTest extends TestCase
{
    private function makeService(SettingRepository $repository, AuditLogger $auditLogger): SettingsService
    {
        return new SettingsService($repository, $auditLogger);
    }

    public function testSetModuleParameterWithParentActiveDoesNotThrow(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $auditLogger = $this->createMock(AuditLogger::class);

        $repository->method('get')->willReturn('1');
        $repository->expects(self::once())->method('saveMany');

        $service = $this->makeService($repository, $auditLogger);
        $service->set(ModuleParameterEnum::ErpEnabled->value, '1');
    }

    public function testSetModuleParameterWithParentDisabledThrowsCascadeViolation(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $auditLogger = $this->createMock(AuditLogger::class);

        $repository->method('get')->willReturn('0');

        $service = $this->makeService($repository, $auditLogger);

        $this->expectException(CascadeViolationException::class);
        $service->set(ModuleParameterEnum::ErpEnabled->value, '1');
    }

    public function testSetModuleParameterToZeroCascadesChildren(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $auditLogger = $this->createMock(AuditLogger::class);

        $capturedWrites = null;
        $repository->expects(self::once())
            ->method('saveMany')
            ->with(self::callback(function (array $writes) use (&$capturedWrites): bool {
                $capturedWrites = $writes;

                return true;
            }));

        $service = $this->makeService($repository, $auditLogger);
        $service->set(ModuleParameterEnum::VaultEnabled->value, '0');

        $keys = array_column($capturedWrites, 0);
        self::assertContains(ModuleParameterEnum::VaultEnabled->value, $keys);
        self::assertContains(ModuleParameterEnum::VaultSafeEnabled->value, $keys);
        self::assertContains(ModuleParameterEnum::VaultPasswordGeneratorEnabled->value, $keys);

        foreach ($capturedWrites as [$key, $value]) {
            self::assertSame('0', $value);
        }
    }

    public function testSetApplicationParameterDoesNotCascade(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $auditLogger = $this->createMock(AuditLogger::class);

        $repository->expects(self::once())
            ->method('saveMany')
            ->with([[$_key = ApplicationParameterEnum::SiteName->value, 'My Site']]);

        $service = $this->makeService($repository, $auditLogger);
        $service->set(ApplicationParameterEnum::SiteName->value, 'My Site');
    }

    public function testSetCallsAuditLoggerAfterPersistence(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $auditLogger = $this->createMock(AuditLogger::class);

        $repository->method('get')->willReturn('1');

        $auditLogger->expects(self::once())
            ->method('log')
            ->with(
                'core',
                'settings.updated',
                null,
                null,
                ['key' => ModuleParameterEnum::CrmEnabled->value, 'value' => '1'],
            );

        $service = $this->makeService($repository, $auditLogger);
        $service->set(ModuleParameterEnum::CrmEnabled->value, '1');
    }

    public function testSetUnknownKeyPersistsWithoutCascade(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $auditLogger = $this->createMock(AuditLogger::class);

        $repository->expects(self::once())
            ->method('saveMany')
            ->with([['unknown_custom_key', 'some_value']]);

        $service = $this->makeService($repository, $auditLogger);
        $service->set('unknown_custom_key', 'some_value');
    }
}
