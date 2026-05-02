<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Service;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Exception\CascadeViolationException;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Orchestrates writes to application parameters. Enforces the module
 * dependency graph (defined on ApplicationParameterEnum) on top of the
 * pure-persistence concerns owned by SettingRepository.
 *
 *  - Refuses enabling a parameter whose parent module is off
 *  - Cascades disable to all transitive children when a parent goes off
 *
 * The repository is intentionally kept rule-free so direct persistence
 * (migrations, seeders, the sync command) is not double-validated.
 */
final readonly class SettingsManager
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
        $parameter = ApplicationParameterEnum::tryFrom($key);

        if (null !== $parameter && '1' === $value) {
            $this->assertParentEnabled($parameter);
        }

        $writes = [[$key, $value]];

        if (null !== $parameter && '0' === $value) {
            foreach ($parameter->getCascadeDisableTargets() as $childKey) {
                $writes[] = [$childKey, '0'];
            }
        }

        $this->repository->saveMany($writes);

        $this->auditLogger->log('core', 'settings.updated', null, null, ['key' => $key, 'value' => $value]);
    }

    private function assertParentEnabled(ApplicationParameterEnum $parameter): void
    {
        $parentKey = $parameter->getCascadeRequires();
        if (null === $parentKey) {
            return;
        }

        $parentEnum = ApplicationParameterEnum::tryFrom($parentKey);
        $parentValue = null !== $parentEnum
            ? $this->repository->getOrDefault($parentEnum)
            : ($this->repository->get($parentKey) ?? '0');

        if ('1' !== $parentValue) {
            throw new CascadeViolationException($parameter->value, $parentKey);
        }
    }
}
