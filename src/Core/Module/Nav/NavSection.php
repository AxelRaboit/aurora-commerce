<?php

declare(strict_types=1);

namespace Aurora\Core\Module\Nav;

final readonly class NavSection
{
    /**
     * @param string    $id       Stable technical identifier (e.g. `'crm'`, `'billing.invoicing'`).
     *                            **Immuable** : utilisé pour persister les préférences utilisateur
     *                            (`CoreUserInterface::getHiddenNavSections()`). Renommer un `id` =
     *                            breaking change (équivalent à supprimer la section + en créer une nouvelle).
     *                            Le label visible vient de l'i18n / alias, jamais d'ici.
     * @param NavItem[] $items
     * @param int       $priority Lower = renders first. Defaults to 100. Use higher values (e.g. 1000)
     *                            for system/meta sections that should sit at the bottom of the nav.
     */
    public function __construct(
        public string $id,
        public array $items,
        public int $priority = 100,
    ) {}
}
