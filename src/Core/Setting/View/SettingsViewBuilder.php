<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin settings page. Centralises the
 * grouped parameters + media URL resolution so the controller stays focused
 * on JSON update flow.
 */
final readonly class SettingsViewBuilder
{
    public function __construct(
        private SettingRepository $settingRepository,
        private MediaRepository $mediaRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        $groups = [];

        foreach (ApplicationParameterEnum::cases() as $parameter) {
            if (!$parameter->isAdminAccessible()) {
                continue;
            }

            $groupName = $parameter->getGroup();

            $value = $this->settingRepository->get($parameter->getKey(), $parameter->getDefaultValue());

            // EmailLocale defaults to the site's DefaultLocale when never explicitly set —
            // keeps customer emails consistent with the site language out of the box.
            if (ApplicationParameterEnum::EmailLocale === $parameter && '' === $value) {
                $value = $this->settingRepository->get(
                    ApplicationParameterEnum::DefaultLocale->value,
                    ApplicationParameterEnum::DefaultLocale->getDefaultValue(),
                );
            }

            $groups[$groupName][] = [
                'key' => $parameter->getKey(),
                'label' => $parameter->getLabel(),
                'description' => $parameter->getDescription(),
                'type' => $parameter->getType(),
                'group' => $groupName,
                'value' => $value,
                'requires' => $parameter->getCascadeRequires(),
                'mediaUrl' => 'media' === $parameter->getType() ? $this->resolveMediaUrl($value) : null,
            ];
        }

        return [
            'groups' => $groups,
            'mediaPickerPath' => $this->urlGenerator->generate('backend_media'),
            'postSearchPath' => $this->urlGenerator->generate('backend_posts_search'),
        ];
    }

    public function resolveMediaUrl(?string $rawId): ?string
    {
        if (null === $rawId || '' === $rawId) {
            return null;
        }

        $mediaId = (int) $rawId;
        if ($mediaId <= 0) {
            return null;
        }

        return $this->mediaRepository->find($mediaId)?->getPublicUrl();
    }
}
