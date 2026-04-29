<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class SiteBrandingExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly MediaRepository $mediaRepository,
    ) {}

    public function getGlobals(): array
    {
        return [
            'siteName' => $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName),
            'siteDescription' => $this->settingRepository->get(
                ApplicationParameterEnum::SiteDescription->value,
                ApplicationParameterEnum::SiteDescription->getDefaultValue(),
            ) ?? '',
            'siteLogoUrl' => $this->resolveMediaUrl(ApplicationParameterEnum::LogoMediaId),
            'siteFaviconUrl' => $this->resolveMediaUrl(ApplicationParameterEnum::FaviconMediaId),
        ];
    }

    private function resolveMediaUrl(ApplicationParameterEnum $parameter): ?string
    {
        $rawId = $this->settingRepository->get($parameter->value, '');
        if (null === $rawId || '' === $rawId) {
            return null;
        }

        $mediaId = (int) $rawId;
        if ($mediaId <= 0) {
            return null;
        }

        $media = $this->mediaRepository->find($mediaId);

        return $media?->getPublicUrl();
    }
}
