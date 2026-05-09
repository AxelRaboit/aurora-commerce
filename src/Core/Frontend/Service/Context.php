<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Service;

use Aurora\Core\Locale\Entity\LocaleInterface;
use Aurora\Core\Locale\Repository\LocaleRepository;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Doctrine\Common\Collections\Order;

/**
 * Aggregates site-wide configuration used by public-facing controllers.
 */
final class Context
{
    /** @var list<LocaleInterface>|null */
    private ?array $cachedLocales = null;

    private ?LocaleInterface $cachedDefault = null;

    public function __construct(
        private readonly LocaleRepository $localeRepository,
        private readonly SettingRepository $settingRepository,
    ) {}

    /** @return list<LocaleInterface> */
    public function activeLocales(): array
    {
        if (null === $this->cachedLocales) {
            $this->cachedLocales = $this->localeRepository->findBy(
                ['isActive' => true],
                ['position' => Order::Ascending->value, 'code' => Order::Ascending->value],
            );
        }

        return $this->cachedLocales;
    }

    /** @return list<string> */
    public function activeLocaleCodes(): array
    {
        return array_map(static fn (LocaleInterface $locale): string => $locale->getCode(), $this->activeLocales());
    }

    public function defaultLocale(): string
    {
        if (!$this->cachedDefault instanceof LocaleInterface) {
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
