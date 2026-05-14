<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Twig\Attribute\AsTwigFunction;

final class LocaleExtension
{
    /** @var array<string, string> ISO 3166-1 alpha-2 flag code per locale */
    private const FLAG_CODES = [
        'fr' => 'fr',
        'en' => 'gb',
        'es' => 'es',
        'de' => 'de',
        'it' => 'it',
        'pt' => 'pt',
        'nl' => 'nl',
    ];

    /** @var array<string, string> */
    private const NAMES = [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'nl' => 'Nederlands',
    ];

    #[AsTwigFunction(name: 'locale_flag')]
    public function localeFlag(string $code): ?string
    {
        return self::FLAG_CODES[$code] ?? null;
    }

    #[AsTwigFunction(name: 'locale_name')]
    public function localeName(string $code): ?string
    {
        return self::NAMES[$code] ?? null;
    }
}
