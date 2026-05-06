<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Service;

use Aurora\Core\Media\Contract\MediaUsageProviderInterface;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
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
            [ApplicationParameterEnum::LogoMediaId, 'admin.media.usage.brandingLogo'],
            [ApplicationParameterEnum::FaviconMediaId, 'admin.media.usage.brandingFavicon'],
        ];
        foreach ($checks as [$param, $labelKey]) {
            $value = (int) ($this->settingRepository->get($param->value, '') ?? '');
            if ($value === $mediaId) {
                $usages[] = [
                    'type' => 'branding.setting',
                    'label' => $this->translator->trans($labelKey),
                    'detail' => $this->translator->trans('admin.media.usage.brandingDetail'),
                    'href' => $settingsUrl,
                ];
            }
        }

        return $usages;
    }
}
