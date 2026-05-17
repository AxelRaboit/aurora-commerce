<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Setting\Configuration\SettingDefinitionRegistry;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds the Twig payload for the dev parameters dashboard tab.
 *
 * Only surfaces settings that are NOT already exposed in /backend/settings
 * with proper context (tabs, labels, descriptions). Anything the
 * {@see SettingDefinitionRegistry} knows about is admin-accessible and
 * belongs there — showing it here too would duplicate the UI and confuse
 * operators about where to edit it.
 *
 * The dev dashboard therefore focuses on:
 *  - module toggle flags (ModuleParameterEnum)
 *  - technical settings intentionally kept out of the admin UI
 *    (sequences, internal prefixes, hidden feature flags, etc.)
 */
final readonly class ParametersViewBuilder
{
    public function __construct(
        private SettingRepository $settingRepository,
        private TranslatorInterface $translator,
        private SettingDefinitionRegistry $definitionRegistry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function parametersPayload(int $page, ?string $search, ?string $group = null): array
    {
        // Collect all keys already surfaced in /backend/settings so the DB
        // query excludes them — keeps pagination counts accurate.
        $excludeKeys = array_keys(array_filter(
            array_combine(
                array_map(fn ($c): string => $c->getKey(), ApplicationParameterEnum::cases()),
                array_fill(0, count(ApplicationParameterEnum::cases()), true),
            ),
            fn (bool $_, string $key): bool => $this->definitionRegistry->isAdminAccessible($key),
            ARRAY_FILTER_USE_BOTH,
        ));

        $result = $this->settingRepository->findPaginated($page, search: $search, group: $group, excludeKeys: $excludeKeys);

        $labelsByKey = [];
        foreach (ApplicationParameterEnum::cases() as $case) {
            $labelsByKey[$case->getKey()] = $case->getLabel();
        }

        foreach (ModuleParameterEnum::cases() as $case) {
            $labelsByKey[$case->getKey()] = $case->getLabel();
        }

        $items = array_map(
            fn ($parameter): array => [
                'key' => $parameter->getKey(),
                'label' => $this->translator->trans($labelsByKey[$parameter->getKey()] ?? $parameter->getKey()),
                'value' => $parameter->getValue(),
                'description' => $this->translator->trans($parameter->getDescription()),
                'type' => $parameter->getType(),
                'group' => $parameter->getGroup(),
            ],
            $result['items'],
        );

        $groups = array_values(array_filter(array_unique(
            array_map(fn ($c): string => $c->getGroup(), ApplicationParameterEnum::cases()),
        ), fn (string $g): bool => ModuleParameterEnum::MODULE !== $g));
        sort($groups);

        return [
            'success' => true,
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'groups' => $groups,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload, ?string $search, ?string $group): array
    {
        return [
            'tab' => 'parameters',
            'parameters' => $payload,
            'search' => $search ?? '',
            'group' => $group ?? '',
        ];
    }
}
