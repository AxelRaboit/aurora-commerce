<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Storage\Enum\MimeTypeEnum;
use Aurora\Core\Storage\Enum\StorageAreaEnum;
use Aurora\Core\Storage\Service\ImageCropper;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Dto\MediaFolderInputInterface;
use Aurora\Module\Media\Library\Dto\MediaInputInterface;
use Aurora\Module\Media\Library\Entity\Media;
use Aurora\Module\Media\Library\Entity\MediaFolder;
use Aurora\Module\Media\Library\Entity\MediaFolderInterface;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Media\Library\Entity\MediaVersion;
use Aurora\Module\Media\Library\Entity\MediaVersionInterface;
use Aurora\Module\Media\Library\Repository\MediaFolderRepository;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Aurora\Module\Media\Library\Repository\MediaVersionRepository;
use Aurora\Module\Media\Library\Service\ImageVariantGenerator;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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
        protected readonly MediaVersionRepository $versionRepository,
        protected readonly ImageVariantGenerator $variantGenerator,
        protected readonly ImageCropper $imageCropper,
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

        $this->recordVersion($media);

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
        // Remove the current file + every historical version file (each crop
        // wrote a fresh path, so versions own distinct files on disk). The
        // version rows themselves cascade-delete with the media.
        $paths = [Path::join($this->uploadDir, $media->getPath())];
        foreach ($this->versionRepository->findByMedia($media) as $version) {
            $paths[] = Path::join($this->uploadDir, $version->getPath());
        }
        $this->filesystem->remove($paths);

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
        if (null === $mime) {
            return;
        }

        $oldPath = $media->getPath();
        $oldVariants = $media->getVariants();

        // Version-preserving crop (like GED): write the result to a fresh file
        // next to the source, leaving the original bytes intact for the prior
        // version row. The current media then points at the new file.
        $directory = Path::getDirectory($oldPath);
        $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
        $base = $this->slugger->slug(pathinfo((string) $media->getOriginalName(), PATHINFO_FILENAME))->lower();
        $newFilename = sprintf('%s-%s.%s', $base, uniqid(), $extension);
        $newPath = '' !== $directory ? sprintf('%s/%s', $directory, $newFilename) : $newFilename;

        $dimensions = $this->imageCropper->crop(
            Path::join($this->uploadDir, $oldPath),
            Path::join($this->uploadDir, $newPath),
            $mime->value,
            $x,
            $y,
            $width,
            $height,
        );
        if (null === $dimensions) {
            return;
        }

        $media->setPath($newPath);
        $media->setFilename($newFilename);
        $media->setWidth($dimensions[0]);
        $media->setHeight($dimensions[1]);
        $media->setSize((int) (@filesize(Path::join($this->uploadDir, $newPath)) ?: $media->getSize()));
        $media->setVariants($this->variantGenerator->generate($newPath, $mime->value));

        $this->entityManager->flush();

        // The old file stays on disk (it backs the prior version); only its
        // now-orphaned variants are dropped.
        $this->variantGenerator->deleteVariants($oldVariants);

        $this->recordVersion($media);
        $this->auditLogger->log('media', 'cropped', 'Media', $media->getId(), [
            ...$this->auditMediaPayload($media),
            'crop' => ['x' => $x, 'y' => $y, 'w' => $width, 'h' => $height],
        ]);
    }

    /**
     * Snapshots the media's current file metadata onto a new version row.
     * Called whenever a new physical file becomes current (upload, crop).
     */
    protected function recordVersion(MediaInterface $media): void
    {
        $version = $this->createMediaVersion();
        $version->setMedia($media)
            ->setPath((string) $media->getPath())
            ->setFilename((string) $media->getFilename())
            ->setOriginalName((string) $media->getOriginalName())
            ->setMimeType((string) $media->getMimeType())
            ->setSize((int) $media->getSize())
            ->setWidth($media->getWidth())
            ->setHeight($media->getHeight())
            ->setVersionNumber($this->versionRepository->getNextVersionNumber($media));
        $this->entityManager->persist($version);
        $this->entityManager->flush();
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

    protected function createMediaVersion(): MediaVersionInterface
    {
        return new MediaVersion();
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
