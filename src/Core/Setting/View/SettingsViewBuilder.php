<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Setting\Configuration\SettingDefinitionRegistry;
use Aurora\Core\Setting\Repository\SettingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Aurora\Core\Media\Service\MediaUrlGenerator;

/**
 * Builds the Twig payload for the admin settings page. Iterates the
 * {@see SettingDefinitionRegistry} (built from every contributed
 * {@see ConfigurationTabProviderInterface}),
 * resolves the current persisted value for each field, and decorates `media`
 * fields with a public URL the Vue layer can preview.
 *
 * Wire format kept stable: a `groups` map (tab id → field[]) plus a `tabs`
 * list carrying ordering metadata so the JS no longer needs to hardcode the
 * tab order.
 */
final readonly class SettingsViewBuilder
{
    public function __construct(
        private SettingRepository $settingRepository,
        private MediaRepository $mediaRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private SettingDefinitionRegistry $definitionRegistry,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        $groups = [];
        $tabs = [];

        foreach ($this->definitionRegistry->getTabs() as $tab) {
            $fields = [];
            foreach ($tab->fields as $field) {
                $value = $this->settingRepository->get($field->key, $field->defaultValue);

                $fields[] = [
                    'key' => $field->key,
                    'label' => $this->translator->trans($field->labelKey),
                    'description' => $this->translator->trans($field->descriptionKey),
                    'type' => $field->type,
                    'group' => $tab->id,
                    'value' => $value,
                    'mediaUrl' => 'media' === $field->type ? $this->resolveMediaUrl($value) : null,
                    'options' => $field->options,
                ];
            }

            $groups[$tab->id] = $fields;
            $tabs[] = [
                'id' => $tab->id,
                'priority' => $tab->priority,
                'alwaysVisible' => $tab->alwaysVisible,
                'componentName' => $tab->componentName,
            ];
        }

        return [
            'groups' => $groups,
            'tabs' => $tabs,
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

        return $this->mediaUrlGenerator->publicUrl($this->mediaRepository->find($mediaId));
    }
}
