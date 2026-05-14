<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce;

use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final class EcommerceFrontDescriptor implements FrontendInterface
{
    public function getSlug(): string
    {
        return 'ecommerce';
    }

    public function getLabel(): string
    {
        return 'Ecommerce';
    }

    public function getHomeRoute(): string
    {
        return 'frontend_shop_index';
    }

    public function getPriority(): int
    {
        return 5;
    }

    public function getModuleSettingKey(): string
    {
        return ModuleParameterEnum::EcommerceFrontend->value;
    }

    public function getRoutePrefixes(): array
    {
        return [
            'frontend_shop_',
            'frontend_cart',
            'frontend_checkout',
            'frontend_order_',
            'frontend_account_orders',
        ];
    }
}
