<?php

declare(strict_types=1);

namespace App\Enum\Menu;

enum MenuItemTargetTypeEnum: string
{
    case Post = 'post';
    case Term = 'term';
    case PostTypeArchive = 'post_type_archive';
    case Home = 'home';
    case CustomUrl = 'custom_url';
    case FrontLogin = 'front_login';
    case FrontRegister = 'front_register';
    case FrontAccount = 'front_account';
    case FrontLogout = 'front_logout';

    public function labelKey(): string
    {
        return sprintf('admin.menus.targetTypes.%s', $this->value);
    }

    public function requiresTargetId(): bool
    {
        return match ($this) {
            self::Post, self::Term, self::PostTypeArchive => true,
            default => false,
        };
    }

    public function requiresCustomUrl(): bool
    {
        return self::CustomUrl === $this;
    }
}
