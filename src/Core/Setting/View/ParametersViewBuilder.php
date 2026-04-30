<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
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
    public function parametersPayload(int $page, ?string $search): array
    {
        $result = $this->settingRepository->findPaginated($page, search: $search);

        $labelsByKey = [];
        foreach (ApplicationParameterEnum::cases() as $case) {
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

        return [
            'success' => true,
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload, ?string $search): array
    {
        return [
            'tab' => 'parameters',
            'parameters' => $payload,
            'search' => $search ?? '',
        ];
    }
}
