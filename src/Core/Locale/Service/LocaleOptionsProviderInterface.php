<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Service;

interface LocaleOptionsProviderInterface
{
    /**
     * Options de locale exploitables côté Vue (onglets / selects), filtrées
     * sur les locales actives (en single-locale mode, ne contient que la
     * locale par défaut).
     *
     * @return list<array{code: string, label: string}>
     */
    public function getActiveOptions(): array;
}
