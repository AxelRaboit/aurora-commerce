<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Provider;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contributes a set of `ApplicationParameterEnumInterface` cases to the
 * `aurora:application-parameter` sync command. Implementations are auto-
 * tagged via `#[AutoconfigureTag]` — any service implementing this
 * interface (with the default `autoconfigure: true` in services.yaml)
 * is picked up by the command's tagged iterator and its enum cases get
 * synced to the `core_settings` table.
 *
 * Aurora-core ships two default providers (one per built-in enum:
 * `ApplicationParameterEnum`, `ModuleParameterEnum`). Client projects
 * register their own settings by implementing this interface and
 * yielding their custom enum's cases.
 *
 * Example (client side, after declaring a custom WeldingSettingEnum):
 *
 *     final readonly class WeldingApplicationParameterProvider
 *         implements ApplicationParameterProviderInterface
 *     {
 *         public function getParameters(): iterable
 *         {
 *             yield from WeldingSettingEnum::cases();
 *         }
 *     }
 *
 * Critically: the `aurora:application-parameter` command **deletes**
 * any `core_settings` row whose key isn't returned by *any* provider
 * (flagged "obsolète"). So a client custom enum **must** be exposed via
 * a provider — otherwise saved values will be wiped on the next sync.
 */
#[AutoconfigureTag('aurora.application_parameter_provider')]
interface ApplicationParameterProviderInterface
{
    /** @return iterable<ApplicationParameterEnumInterface> */
    public function getParameters(): iterable;
}
