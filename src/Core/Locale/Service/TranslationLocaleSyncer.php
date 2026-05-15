<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function in_array;

#[AsAlias(id: TranslationLocaleSyncerInterface::class)]
class TranslationLocaleSyncer implements TranslationLocaleSyncerInterface
{
    public function __construct(
        protected readonly LocaleContextInterface $localeContext,
    ) {}

    public function stale(iterable $existing, array $inputLocales): array
    {
        $activeLocales = $this->localeContext->getActiveLocales();
        $stale = [];

        foreach ($existing as $locale => $translation) {
            $code = (string) $locale;

            if (!in_array($code, $activeLocales, true)) {
                continue;
            }

            if (!in_array($code, $inputLocales, true)) {
                $stale[] = $translation;
            }
        }

        return $stale;
    }
}
