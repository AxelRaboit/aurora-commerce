<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Builds the Twig payload for the dev parameters dashboard tab. Centralises
 * the pagination + per-row labelling shape so the controller stays focused
 * on flow (XHR vs full page rendering, search query).
 */
final readonly class ParametersViewBuilder
{
    public function __construct(private SettingRepository $settingRepository) {}

    /**
     * @return array<string, mixed>
     */
    public function parametersPayload(int $page, ?string $search, ?string $group = null): array
    {
        $result = $this->settingRepository->findPaginated($page, search: $search, group: $group);

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
                'label' => $labelsByKey[$parameter->getKey()] ?? $parameter->getKey(),
                'value' => $parameter->getValue(),
                'description' => $parameter->getDescription(),
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
