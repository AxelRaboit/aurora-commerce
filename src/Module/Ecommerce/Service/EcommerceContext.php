<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

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
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::EcommerceEnabled->value, true);
    }

    public function isFrontEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::EcommerceShopEnabled->value, true);
    }
}
