<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Locale;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Repository\LocaleRepository;
use App\Repository\SettingRepository;

/**
 * Aggregates site-wide configuration used by public-facing controllers.
 */
final class FrontContext
{
    /** @var list<Locale>|null */
    private ?array $cachedLocales = null;

    private ?Locale $cachedDefault = null;

    public function __construct(
        private readonly LocaleRepository $localeRepository,
        private readonly SettingRepository $settingRepository,
    ) {}

    /** @return list<Locale> */
    public function activeLocales(): array
    {
        if (null === $this->cachedLocales) {
            $this->cachedLocales = $this->localeRepository->findBy(
                ['isActive' => true],
                ['position' => 'ASC', 'code' => 'ASC'],
            );
        }

        return $this->cachedLocales;
    }

    /** @return list<string> */
    public function activeLocaleCodes(): array
    {
        return array_map(static fn (Locale $locale): string => $locale->getCode(), $this->activeLocales());
    }

    public function defaultLocale(): string
    {
        if (!$this->cachedDefault instanceof Locale) {
            foreach ($this->activeLocales() as $locale) {
                if ($locale->isDefault()) {
                    $this->cachedDefault = $locale;
                    break;
                }
            }
        }

        return $this->cachedDefault?->getCode()
            ?? $this->settingRepository->get(ApplicationParameterEnum::DefaultLocale->value, 'fr')
            ?? 'fr';
    }

    public function isLocaleActive(string $code): bool
    {
        return in_array($code, $this->activeLocaleCodes(), true);
    }

    public function setting(string $key, ?string $default = null): ?string
    {
        return $this->settingRepository->get($key, $default);
    }

    public function siteName(): string
    {
        return $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);
    }

    public function siteDescription(): ?string
    {
        return $this->setting(ApplicationParameterEnum::SiteDescription->value, null);
    }

    public function siteUrl(): string
    {
        return mb_rtrim($this->setting(ApplicationParameterEnum::SiteUrl->value, 'http://localhost') ?? 'http://localhost', '/');
    }

    public function homepagePostId(): ?int
    {
        $value = $this->setting(ApplicationParameterEnum::HomepagePostId->value, '');

        return null !== $value && '' !== $value ? (int) $value : null;
    }
}
