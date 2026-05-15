<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Service;

interface TranslationLocaleSyncerInterface
{
    /**
     * Parmi les translations existantes en DB (indexées par locale), retourne
     * celles qu'il faut supprimer pour refléter l'input :
     *
     *  - les locales hors du mode actif (ex: EN quand single FR) sont
     *    **préservées** systématiquement (réversibilité du single-locale mode) ;
     *  - les locales actives absentes de l'input sont marquées pour suppression.
     *
     * @template T
     *
     * @param iterable<string, T> $existing     existing translations keyed by locale
     * @param list<string>        $inputLocales locales présentes dans l'input
     *
     * @return list<T>
     */
    public function stale(iterable $existing, array $inputLocales): array;
}
