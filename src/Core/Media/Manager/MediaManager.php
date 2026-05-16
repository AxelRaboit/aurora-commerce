<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Dto\MediaFolderInputInterface;
use Aurora\Core\Media\Dto\MediaInputInterface;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Aurora\Core\Media\Entity\MediaFolderInterface;
use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Media\Enum\MimeTypeEnum;
use Aurora\Core\Media\Enum\StorageAreaEnum;
use Aurora\Core\Media\Repository\MediaFolderRepository;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Media\Service\ImageVariantGenerator;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Support\Num;
use Aurora\Core\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use GdImage;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(MediaManagerInterface::class)]
class MediaManager implements MediaManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly SluggerInterface $slugger,
        protected readonly MediaFolderRepository $folderRepository,
        protected readonly MediaRepository $mediaRepository,
        protected readonly ImageVariantGenerator $variantGenerator,
        protected readonly TranslatorInterface $translator,
        protected readonly Security $security,
        protected readonly AuditLogger $auditLogger,
        protected readonly Filesystem $filesystem,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
        #[Autowire('%app.upload_dir%')]
        protected readonly string $uploadDir,
    ) {}

    // ── Media: upload + CRUD ──────────────────────────────────────────────────

    public function upload(UploadedFile $file, ?MediaFolderInterface $folder = null, StorageAreaEnum $area = StorageAreaEnum::Media): MediaInterface
    {
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $clientName = $file->getClientOriginalName();

        $safeFilename = $this->slugger->slug(pathinfo($clientName, PATHINFO_FILENAME))->lower();
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $dateSlug = new DateTimeImmutable()->format('Y/m');
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);
        $relativeDir = sprintf('%s/%s', $area->value, $dateSlug);
        $relativePath = sprintf('%s/%s', $relativeDir, $newFilename);

        $this->filesystem->mkdir(Path::join($this->uploadDir, $relativeDir));
        $file->move(Path::join($this->uploadDir, $relativeDir), $newFilename);

        [$width, $height] = @getimagesize(Path::join($this->uploadDir, $relativePath)) ?: [null, null];

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CoreMediaPrefix->value, SequencePrefixEnum::Media->value) ?? SequencePrefixEnum::Media->value;

        $media = $this->createMedia();
        $media->setFilename($newFilename);
        $media->setOriginalName($clientName);
        $media->setMimeType($mimeType);
        $media->setSize($size);
        $media->setPath($relativePath);
        $media->setWidth($width);
        $media->setHeight($height);
        $media->setFolder($folder);
        $media->setVariants($this->variantGenerator->generate($relativePath, (string) $mimeType));
        $media->setReference($this->sequenceGenerator->next($prefix));

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $media->setUploadedBy($user);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $this->auditLogger->log('media', 'uploaded', 'Media', $media->getId(), $this->auditMediaPayload($media));

        return $media;
    }

    public function update(MediaInterface $media, MediaInputInterface $input): void
    {
        $this->applyMediaInput($media, $input);
        $this->entityManager->flush();
        $this->auditMediaUpdated($media);
    }

    public function move(MediaInterface $media, ?MediaFolderInterface $folder): void
    {
        $media->setFolder($folder);
        $this->entityManager->flush();
        $this->auditLogger->log('media', 'moved', 'Media', $media->getId(), [
            ...$this->auditMediaPayload($media),
            'folder' => $folder?->getName(),
        ]);
    }

    public function delete(MediaInterface $media): void
    {
        $filePath = Path::join($this->uploadDir, $media->getPath());
        $this->filesystem->remove($filePath);

        $this->variantGenerator->deleteVariants($media->getVariants());

        $this->auditMediaDeleted($media);

        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }

    // ── MediaFolder: CRUD ─────────────────────────────────────────────────────

    public function createFolder(MediaFolderInputInterface $input): MediaFolderInterface
    {
        $folder = $this->createMediaFolder();
        $folder->setName($input->getName());

        if (null !== $input->getParentId()) {
            $parent = $this->folderRepository->find($input->getParentId());
            if (null === $parent) {
                throw new InvalidArgumentException($this->translator->trans('backend.media.errors.parent_folder_not_found', ['{id}' => $input->getParentId()]));
            }

            $folder->setParent($parent);
        }

        $folderPrefix = $this->settingRepository->get(ApplicationParameterEnum::CoreMediaFolderPrefix->value, SequencePrefixEnum::MediaFolder->value) ?? SequencePrefixEnum::MediaFolder->value;
        $folder->setReference($this->sequenceGenerator->next($folderPrefix));

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        $this->auditFolderCreated($folder);

        return $folder;
    }

    public function updateFolder(MediaFolderInterface $folder, MediaFolderInputInterface $input): void
    {
        $folder->setName($input->getName());

        $newParent = null;
        if (null !== $input->getParentId()) {
            $newParent = $this->folderRepository->find($input->getParentId());
            if (null === $newParent) {
                throw new InvalidArgumentException($this->translator->trans('backend.media.errors.parent_folder_not_found', ['{id}' => $input->getParentId()]));
            }

            if ($newParent === $folder || $newParent->isDescendantOf($folder)) {
                throw new InvalidArgumentException($this->translator->trans('backend.media.errors.folder_self_nested'));
            }
        }

        $folder->setParent($newParent);

        $this->entityManager->flush();

        $this->auditFolderUpdated($folder);
    }

    public function deleteFolder(MediaFolderInterface $folder): void
    {
        $this->auditFolderDeleted($folder);

        // FK onDelete: SET NULL on media/media_folders children → they bubble up to root.
        $this->entityManager->remove($folder);
        $this->entityManager->flush();
    }

    // ── Bulk + reorder + crop ─────────────────────────────────────────────────

    public function reorder(array $orderedIds): void
    {
        if ([] === $orderedIds) {
            return;
        }

        $mediaById = [];
        foreach ($this->mediaRepository->findBy(['id' => $orderedIds]) as $media) {
            $mediaById[(int) $media->getId()] = $media;
        }

        foreach ($orderedIds as $position => $id) {
            if (isset($mediaById[$id])) {
                $mediaById[$id]->setPosition($position);
            }
        }

        $this->entityManager->flush();
    }

    public function bulkDelete(array $ids): void
    {
        if ([] === $ids) {
            return;
        }

        foreach ($this->mediaRepository->findBy(['id' => $ids]) as $media) {
            $filePath = Path::join($this->uploadDir, $media->getPath());
            $this->filesystem->remove($filePath);

            $this->variantGenerator->deleteVariants($media->getVariants());
            $this->entityManager->remove($media);
        }

        $this->entityManager->flush();
    }

    public function bulkMove(array $ids, ?MediaFolderInterface $folder): void
    {
        if ([] === $ids) {
            return;
        }

        foreach ($this->mediaRepository->findBy(['id' => $ids]) as $media) {
            $media->setFolder($folder);
        }

        $this->entityManager->flush();
    }

    public function crop(MediaInterface $media, int $x, int $y, int $width, int $height): void
    {
        $mime = MimeTypeEnum::tryFrom($media->getMimeType());
        $sourceAbsolute = Path::join($this->uploadDir, $media->getPath());

        if (!$mime?->isRasterImage() || !is_file($sourceAbsolute)) {
            return;
        }

        $source = match (true) {
            $mime->isJpeg() => @imagecreatefromjpeg($sourceAbsolute),
            MimeTypeEnum::Png === $mime => @imagecreatefrompng($sourceAbsolute),
            MimeTypeEnum::Gif === $mime => @imagecreatefromgif($sourceAbsolute),
            MimeTypeEnum::Webp === $mime => @imagecreatefromwebp($sourceAbsolute),
            default => false,
        };

        if (!$source instanceof GdImage) {
            return;
        }

        $sourceW = imagesx($source);
        $sourceH = imagesy($source);

        // Clamp to image bounds
        $x = Num::clamp($x, 0, $sourceW - 1);
        $y = Num::clamp($y, 0, $sourceH - 1);
        $width = Num::clamp($width, 1, $sourceW - $x);
        $height = Num::clamp($height, 1, $sourceH - $y);

        $cropped = imagecreatetruecolor($width, $height);
        imagecopy($cropped, $source, 0, 0, $x, $y, $width, $height);
        imagedestroy($source);

        match (true) {
            $mime->isJpeg() => imagejpeg($cropped, $sourceAbsolute, 85),
            MimeTypeEnum::Png === $mime => imagepng($cropped, $sourceAbsolute, 6),
            MimeTypeEnum::Gif === $mime => imagegif($cropped, $sourceAbsolute),
            MimeTypeEnum::Webp === $mime => imagewebp($cropped, $sourceAbsolute, 85),
            default => null,
        };

        imagedestroy($cropped);

        // Update dimensions and regenerate variants
        [$newW, $newH] = @getimagesize($sourceAbsolute) ?: [$width, $height];
        $media->setWidth($newW);
        $media->setHeight($newH);

        // Delete old variants and regenerate
        $this->variantGenerator->deleteVariants($media->getVariants());
        $newVariants = $this->variantGenerator->generate($media->getPath(), $mime->value);
        $media->setVariants($newVariants);

        $this->entityManager->flush();
        $this->auditLogger->log('media', 'cropped', 'Media', $media->getId(), [
            ...$this->auditMediaPayload($media),
            'crop' => ['x' => $x, 'y' => $y, 'w' => $width, 'h' => $height],
        ]);
    }

    // ── Hooks: instanciation ──────────────────────────────────────────────────

    protected function createMedia(): MediaInterface
    {
        return new Media();
    }

    protected function createMediaFolder(): MediaFolderInterface
    {
        return new MediaFolder();
    }

    // ── Hooks: hydratation ────────────────────────────────────────────────────

    protected function applyMediaInput(MediaInterface $media, MediaInputInterface $input): void
    {
        $media->setAlt($input->getAlt());
        $media->setCaption($input->getCaption());
        $media->setFocalX($input->getFocalX());
        $media->setFocalY($input->getFocalY());

        $folder = null;
        if (null !== $input->getFolderId()) {
            $folder = $this->folderRepository->find($input->getFolderId());
            if (null === $folder) {
                throw new InvalidArgumentException($this->translator->trans('backend.media.errors.folder_not_found', ['{id}' => $input->getFolderId()]));
            }
        }

        $media->setFolder($folder);
    }

    // ── Hooks: audit ──────────────────────────────────────────────────────────

    protected function auditMediaUpdated(MediaInterface $media): void
    {
        $this->auditLogger->log('media', 'updated', 'Media', $media->getId(), $this->auditMediaPayload($media));
    }

    protected function auditMediaDeleted(MediaInterface $media): void
    {
        $this->auditLogger->log('media', 'deleted', 'Media', $media->getId(), $this->auditMediaPayload($media));
    }

    protected function auditFolderCreated(MediaFolderInterface $folder): void
    {
        $this->auditLogger->log('media', 'folder.created', 'MediaFolder', $folder->getId(), $this->auditFolderPayload($folder));
    }

    protected function auditFolderUpdated(MediaFolderInterface $folder): void
    {
        $this->auditLogger->log('media', 'folder.updated', 'MediaFolder', $folder->getId(), $this->auditFolderPayload($folder));
    }

    protected function auditFolderDeleted(MediaFolderInterface $folder): void
    {
        $this->auditLogger->log('media', 'folder.deleted', 'MediaFolder', $folder->getId(), $this->auditFolderPayload($folder));
    }

    /**
     * Base payload for every Media audit entry. Override to add custom fields:
     * `[...parent::auditMediaPayload($media), 'tags' => $media->getTags()]`.
     *
     * Note: `upload()` also uses this payload — the create event is `uploaded`
     * (not `created`) because the operation has additional file-processing
     * semantics that don't fit the standard `auditCreated` hook signature.
     */
    protected function auditMediaPayload(MediaInterface $media): array
    {
        return ['name' => $media->getOriginalName()];
    }

    protected function auditFolderPayload(MediaFolderInterface $folder): array
    {
        return ['name' => $folder->getName()];
    }
}
