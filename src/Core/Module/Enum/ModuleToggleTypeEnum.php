<?php

declare(strict_types=1);

namespace Aurora\Core\Module\Enum;

/**
 * Classification d'un toggle de {@see ModuleParameterEnum} selon ce
 * qu'il contrôle : un module/sous-module backend (admin) ou un front
 * public (site servi à l'utilisateur final).
 *
 * Surface en pratique via la modale `/dev/dashboard/modules` (un badge
 * "Backend" ou "Frontend" à côté de chaque toggle, pour distinguer
 * d'un coup d'œil les deux axes).
 *
 * Convention de classification (cf. {@see self::fromKey()}) : les clés
 * dont le suffixe est `_frontend` sont des fronts, tout le reste est
 * backend. Cette règle reflète la convention de nommage actuelle
 * `backend_<module>_frontend` introduite par la migration
 * `Version20260511180000`.
 */
enum ModuleToggleTypeEnum: string
{
    case Backend = 'backend';
    case Frontend = 'frontend';

    public static function fromKey(string $settingKey): self
    {
        return str_ends_with($settingKey, '_frontend') ? self::Frontend : self::Backend;
    }
}
