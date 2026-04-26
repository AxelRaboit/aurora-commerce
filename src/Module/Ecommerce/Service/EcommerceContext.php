<?php

declare(strict_types=1);

namespace App\Module\Ecommerce\Service;

use App\Core\Setting\Enum\ApplicationParameterEnum;
use App\Core\Setting\Repository\SettingRepository;

/**
 * Single source of truth for ecommerce module activation.
 *
 * Two independent toggles let the admin run the back-office without exposing the shop
 * publicly (e.g. while seeding products) and vice-versa (turn off admin while keeping
 * the front catalog read-only). Defaults to true for both — i.e. the module is on.
 */
final readonly class EcommerceContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::EcommerceAdminEnabled->value, true);
    }

    public function isFrontEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::EcommerceFrontEnabled->value, true);
    }
}
