<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Service;

use Aurora\Module\Media\Library\Contract\MediaUsageProviderInterface;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BrandingMediaUsageProvider implements MediaUsageProviderInterface
{
    public function __construct(
        private SettingRepository $settingRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function findUsages(int $mediaId): array
    {
        $usages = [];
        $settingsUrl = $this->urlGenerator->generate('backend_settings');

        $checks = [
            [ApplicationParameterEnum::LogoMediaId, 'backend.media.usage.brandingLogo'],
            [ApplicationParameterEnum::FaviconMediaId, 'backend.media.usage.brandingFavicon'],
        ];
        foreach ($checks as [$param, $labelKey]) {
            $value = (int) ($this->settingRepository->get($param->value, '') ?? '');
            if ($value === $mediaId) {
                $usages[] = [
                    'type' => 'branding.setting',
                    'label' => $this->translator->trans($labelKey),
                    'detail' => $this->translator->trans('backend.media.usage.brandingDetail'),
                    'href' => $settingsUrl,
                ];
            }
        }

        return $usages;
    }
}
