<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Service;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(id: LocaleContextInterface::class)]
class LocaleContext implements LocaleContextInterface
{
    private ?bool $singleLocaleMode = null;

    private ?string $defaultLocale = null;

    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        protected readonly SettingRepository $settingRepository,
        #[Autowire(param: 'kernel.enabled_locales')]
        protected readonly array $enabledLocales,
    ) {}

    public function isSingleLocaleMode(): bool
    {
        return $this->singleLocaleMode ??= $this->settingRepository->getBoolean(
            ApplicationParameterEnum::SingleLocaleMode->value,
            false,
        );
    }

    public function getDefaultLocale(): string
    {
        if (null !== $this->defaultLocale) {
            return $this->defaultLocale;
        }

        $value = $this->settingRepository->getOrDefault(ApplicationParameterEnum::DefaultLocale);

        if (!LocaleEnum::isSupported($value)) {
            $value = LocaleEnum::default()->value;
        }

        return $this->defaultLocale = $value;
    }

    public function getActiveLocales(): array
    {
        if ($this->isSingleLocaleMode()) {
            return [$this->getDefaultLocale()];
        }

        return $this->enabledLocales;
    }

    public function getAllLocales(): array
    {
        return $this->enabledLocales;
    }
}
