<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class ModulesViewBuilder
{
    public function __construct(private SettingRepository $settingRepository) {}

    /**
     * @return array<string, mixed>
     */
    public function modulesPayload(): array
    {
        $parameters = [];

        foreach (ApplicationParameterEnum::cases() as $parameter) {
            if ($parameter->getGroup() !== 'modules') {
                continue;
            }

            $parameters[] = [
                'key' => $parameter->getKey(),
                'label' => $parameter->getLabel(),
                'description' => $parameter->getDescription(),
                'value' => $this->settingRepository->get($parameter->getKey(), $parameter->getDefaultValue()),
                'requires' => $parameter->getCascadeRequires(),
            ];
        }

        return ['parameters' => $parameters];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload): array
    {
        return [
            'tab' => 'modules',
            'modules' => $payload,
        ];
    }
}
