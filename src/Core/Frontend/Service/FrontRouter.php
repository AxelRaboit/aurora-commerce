<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Service;

use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use RuntimeException;

final readonly class FrontRouter
{
    public function __construct(
        private FrontRegistry $registry,
        private SettingRepository $settingRepository,
    ) {}

    public function getDefault(): FrontendInterface
    {
        $slug = $this->settingRepository->get(ApplicationParameterEnum::DefaultFront->value, 'editorial') ?? 'editorial';

        return $this->registry->find($slug) ?? $this->registry->highest() ?? throw new RuntimeException('No front registered.');
    }
}
