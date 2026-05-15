<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Service;

use Aurora\Core\Locale\Repository\LocaleRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: LocaleOptionsProviderInterface::class)]
class LocaleOptionsProvider implements LocaleOptionsProviderInterface
{
    public function __construct(
        protected readonly LocaleRepository $localeRepository,
        protected readonly LocaleContextInterface $localeContext,
    ) {
    }

    public function getActiveOptions(): array
    {
        $activeLocales = $this->localeContext->getActiveLocales();

        return array_values(array_filter(
            array_map(static fn ($locale): array => [
                'code' => $locale->getCode(),
                'label' => $locale->getName(),
            ], $this->localeRepository->findAll()),
            static fn (array $locale): bool => \in_array($locale['code'], $activeLocales, true),
        ));
    }
}
