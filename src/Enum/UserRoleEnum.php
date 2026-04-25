<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRoleEnum: string
{
    case User = 'ROLE_USER';
    case Contributor = 'ROLE_CONTRIBUTOR';
    case Author = 'ROLE_AUTHOR';
    case Editor = 'ROLE_EDITOR';
    case Admin = 'ROLE_ADMIN';
    case Dev = 'ROLE_DEV';

    public function priority(): int
    {
        return match ($this) {
            self::Dev => 100,
            self::Admin => 80,
            self::Editor => 60,
            self::Author => 40,
            self::Contributor => 20,
            self::User => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::User => 'Utilisateur',
            self::Contributor => 'Contributeur',
            self::Author => 'Auteur',
            self::Editor => 'Éditeur',
            self::Admin => 'Administrateur',
            self::Dev => 'Développeur',
        };
    }

    /**
     * Returns the highest priority among the given role strings.
     *
     * @param string[] $roles
     */
    public static function highestPriorityForRoles(array $roles): int
    {
        $highest = 0;
        foreach (self::cases() as $role) {
            if (in_array($role->value, $roles, true)) {
                $highest = max($highest, $role->priority());
            }
        }

        return $highest;
    }

    /**
     * @return list<self>
     */
    public static function selectableForAdmin(): array
    {
        return [self::Admin, self::Editor, self::Author, self::Contributor];
    }

    /**
     * @return list<string>
     */
    public static function selectableForAdminValues(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::selectableForAdmin());
    }

    /**
     * All roles that can be assigned (Dev + Admin roles). Used for DTO validation.
     *
     * @return list<string>
     */
    public static function allAssignableValues(): array
    {
        return [self::Dev->value, ...self::selectableForAdminValues()];
    }
}
