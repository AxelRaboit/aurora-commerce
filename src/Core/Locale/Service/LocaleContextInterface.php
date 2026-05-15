<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Service;

interface LocaleContextInterface
{
    public function isSingleLocaleMode(): bool;

    public function getDefaultLocale(): string;

    /**
     * Locales actives à l'affichage / écriture (1 seule si single-locale mode).
     *
     * @return list<string>
     */
    public function getActiveLocales(): array;

    /**
     * Toutes les locales déclarées par le bundle, indépendamment du mode.
     * À utiliser pour les outils statiques (dump de traductions JS, etc.).
     *
     * @return list<string>
     */
    public function getAllLocales(): array;
}
