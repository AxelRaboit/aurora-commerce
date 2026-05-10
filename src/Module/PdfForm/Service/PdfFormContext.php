<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class PdfFormContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::PdfFormEnabled->value, true);
    }

    public function isTemplatesEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::PdfFormTemplatesEnabled->value, true);
    }

    public function isDocumentsEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::PdfFormDocumentsEnabled->value, true);
    }
}
