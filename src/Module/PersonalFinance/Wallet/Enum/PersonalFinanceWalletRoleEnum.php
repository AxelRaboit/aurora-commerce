<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Enum;

enum PersonalFinanceWalletRoleEnum: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function canEdit(): bool
    {
        return self::Viewer !== $this;
    }

    public function canManageMembers(): bool
    {
        return self::Owner === $this;
    }

    public function canDelete(): bool
    {
        return self::Owner === $this;
    }

    /**
     * Roles that can be assigned via invitation. Owner is excluded —
     * ownership transfer goes through a dedicated flow.
     *
     * @return list<self>
     */
    public static function invitable(): array
    {
        return [self::Editor, self::Viewer];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
