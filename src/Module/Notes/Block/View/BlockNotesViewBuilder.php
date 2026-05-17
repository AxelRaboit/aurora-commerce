<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\View;

use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Support\Num;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Notes\Block\Repository\BlockNoteRepository;
use Aurora\Module\Notes\Block\Setting\BlockNoteSettingEnum;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class BlockNotesViewBuilder
{
    public function __construct(
        private BlockNoteRepository $noteRepository,
        private UrlGeneratorInterface $urlGenerator,
        private SettingRepository $settingRepository,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        return [
            'notes' => $this->noteRepository->findFlatListForUser($user),
            'listPath' => $this->urlGenerator->generate('backend_notes_block_list'),
            'showPath' => $this->urlGenerator->generate('backend_notes_block_show', ['id' => '__id__']),
            'createPath' => $this->urlGenerator->generate('backend_notes_block_create'),
            'updatePath' => $this->urlGenerator->generate('backend_notes_block_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_notes_block_delete', ['id' => '__id__']),
            'movePath' => $this->urlGenerator->generate('backend_notes_block_move', ['id' => '__id__']),
            'reorderPath' => $this->urlGenerator->generate('backend_notes_block_reorder'),
            'searchPath' => $this->urlGenerator->generate('backend_notes_block_search'),
            'tagsListPath' => $this->urlGenerator->generate('backend_notes_block_tags_list'),
            'tagsRenamePath' => $this->urlGenerator->generate('backend_notes_block_tags_rename'),
            'tagsMergePath' => $this->urlGenerator->generate('backend_notes_block_tags_merge'),
            'tagsDeletePath' => $this->urlGenerator->generate('backend_notes_block_tags_delete'),
            'imageUploadPath' => $this->urlGenerator->generate('backend_notes_block_images_upload'),
            'imageMaxEdge' => (int) $this->settingRepository->getOrDefault(BlockNoteSettingEnum::ImageMaxEdge),
            'imageQuality' => $this->imageQualityRatio(),
        ];
    }

    private function imageQualityRatio(): float
    {
        return Num::percentToRatio(
            (int) $this->settingRepository->getOrDefault(BlockNoteSettingEnum::ImageQualityPct),
        );
    }
}
