<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Service;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Exception\CascadeViolationException;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Orchestrates writes to application parameters. Enforces the module
 * dependency graph (defined on ModuleParameterEnum) on top of the
 * pure-persistence concerns owned by SettingRepository.
 *
 *  - Refuses enabling a parameter whose parent module is off
 *  - Cascades disable to all transitive children when a parent goes off
 *
 * The repository is intentionally kept rule-free so direct persistence
 * (migrations, seeders, the sync command) is not double-validated.
 */
final readonly class SettingsService
{
    public function __construct(
        private SettingRepository $repository,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @throws CascadeViolationException when enabling a child whose parent is off
     */
    public function set(string $key, ?string $value): void
    {
        $moduleParameter = ModuleParameterEnum::tryFrom($key);

        if (null !== $moduleParameter && '1' === $value) {
            $this->assertParentEnabled($moduleParameter->getCascadeRequires(), $key);
        }

        $writes = [[$key, $value]];

        if (null !== $moduleParameter && '0' === $value) {
            foreach ($moduleParameter->getCascadeDisableTargets() as $childKey) {
                $writes[] = [$childKey, '0'];
            }
        }

        $this->repository->saveMany($writes);

        $this->auditLogger->log('core', 'settings.updated', null, null, ['key' => $key, 'value' => $value]);
    }

    private function assertParentEnabled(?string $parentKey, string $childKey): void
    {
        if (null === $parentKey) {
            return;
        }

        $value = $this->repository->get($parentKey, '1');

        if ('1' !== $value) {
            throw new CascadeViolationException($childKey, $parentKey);
        }
    }
}
