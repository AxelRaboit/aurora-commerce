<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Frontend\Service\Registry;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private Registry $registry,
        private TranslatorInterface $translator,
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

            $groups[$groupName][] = [
                'key' => $parameter->getKey(),
                'label' => $this->translator->trans($parameter->getLabel()),
                'description' => $this->translator->trans($parameter->getDescription()),
                'type' => $parameter->getType(),
                'group' => $groupName,
                'value' => $value,
                'mediaUrl' => 'media' === $parameter->getType() ? $this->resolveMediaUrl($value) : null,
                'options' => 'select' === $parameter->getType() ? $this->resolveSelectOptions($parameter) : null,
            ];
        }

        return [
            'groups' => $groups,
            'mediaPickerPath' => $this->urlGenerator->generate('backend_media'),
            'postSearchPath' => $this->urlGenerator->generate('backend_posts_search'),
        ];
    }

    /** @return list<array{value: string, label: string}>|null */
    private function resolveSelectOptions(ApplicationParameterEnum $parameter): ?array
    {
        if (ApplicationParameterEnum::DefaultFront === $parameter) {
            return array_values(array_map(
                static fn ($front): array => ['value' => $front->getSlug(), 'label' => $front->getLabel()],
                array_filter(
                    $this->registry->all(),
                    fn ($front): bool => null === $front->getModuleSettingKey()
                        || $this->settingRepository->getBoolean($front->getModuleSettingKey(), true),
                ),
            ));
        }

        if (\in_array($parameter, [ApplicationParameterEnum::DefaultLocale, ApplicationParameterEnum::EmailLocale], true)) {
            $options = array_map(
                fn (LocaleEnum $locale): array => [
                    'value' => $locale->value,
                    'label' => $this->translator->trans('shared.locales.'.$locale->value),
                ],
                LocaleEnum::cases(),
            );

            if (ApplicationParameterEnum::EmailLocale === $parameter) {
                array_unshift($options, [
                    'value' => '',
                    'label' => $this->translator->trans('backend.parameters.email_locale_auto'),
                ]);
            }

            return $options;
        }

        if (ApplicationParameterEnum::Timezone === $parameter) {
            return array_map(
                static fn (string $tz): array => ['value' => $tz, 'label' => $tz],
                \DateTimeZone::listIdentifiers(),
            );
        }

        return null;
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
