<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Contract\MediaManagerInterface;
use Aurora\Core\Media\DTO\MediaFolderInput;
use Aurora\Core\Media\DTO\MediaInput;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Aurora\Core\Media\Enum\MimeTypeEnum;
use Aurora\Core\Media\Enum\StorageAreaEnum;
use Aurora\Core\Media\Repository\MediaFolderRepository;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Media\Service\ImageVariantGenerator;
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
final readonly class MediaManager implements MediaManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private MediaFolderRepository $folderRepository,
        private MediaRepository $mediaRepository,
        private ImageVariantGenerator $variantGenerator,
        private TranslatorInterface $translator,
        private Security $security,
        private AuditLogger $auditLogger,
        private Filesystem $filesystem,
        #[Autowire('%app.upload_dir%')]
        private string $uploadDir,
    ) {}

    public function upload(UploadedFile $file, ?MediaFolder $folder = null, StorageAreaEnum $area = StorageAreaEnum::Media): Media
    {
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $clientName = $file->getClientOriginalName();

        $safeFilename = $this->slugger->slug(pathinfo($clientName, PATHINFO_FILENAME))->lower();
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $dateSlug = new DateTimeImmutable()->format('Y-m');
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);
        $relativeDir = sprintf('%s/%s', $area->value, $dateSlug);
        $relativePath = sprintf('%s/%s', $relativeDir, $newFilename);

        $this->filesystem->mkdir(Path::join($this->uploadDir, $relativeDir));
        $file->move(Path::join($this->uploadDir, $relativeDir), $newFilename);

        [$width, $height] = @getimagesize(Path::join($this->uploadDir, $relativePath)) ?: [null, null];

        $media = new Media();
        $media->setFilename($newFilename);
        $media->setOriginalName($clientName);
        $media->setMimeType($mimeType);
        $media->setSize($size);
        $media->setPath($relativePath);
        $media->setWidth($width);
        $media->setHeight($height);
        $media->setFolder($folder);
        $media->setVariants($this->variantGenerator->generate($relativePath, (string) $mimeType));

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $media->setUploadedBy($user);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $this->auditLogger->log('media', 'uploaded', 'Media', $media->getId(), ['name' => $media->getOriginalName()]);

        return $media;
    }

    public function update(Media $media, MediaInput $input): void
    {
        $media->setAlt($input->alt);
        $media->setCaption($input->caption);
        $media->setFocalX($input->focalX);
        $media->setFocalY($input->focalY);

        $folder = null;
        if (null !== $input->folderId) {
            $folder = $this->folderRepository->find($input->folderId);
            if (null === $folder) {
                throw new InvalidArgumentException($this->translator->trans('admin.media.errors.folder_not_found', ['{id}' => $input->folderId]));
            }
        }

        $media->setFolder($folder);
        $this->entityManager->flush();
        $this->auditLogger->log('media', 'updated', 'Media', $media->getId(), ['name' => $media->getOriginalName()]);
    }

    public function move(Media $media, ?MediaFolder $folder): void
    {
        $media->setFolder($folder);
        $this->entityManager->flush();
        $this->auditLogger->log('media', 'moved', 'Media', $media->getId(), [
            'name' => $media->getOriginalName(),
            'folder' => $folder?->getName(),
        ]);
    }

    public function delete(Media $media): void
    {
        $id = $media->getId();
        $name = $media->getOriginalName();

        $filePath = Path::join($this->uploadDir, $media->getPath());
        // Filesystem::remove() is a no-op when the path is missing, so the
        // pre-existence check that wrapped the previous @unlink call is redundant.
        $this->filesystem->remove($filePath);

        $this->variantGenerator->deleteVariants($media->getVariants());
        $this->entityManager->remove($media);
        $this->entityManager->flush();

        $this->auditLogger->log('media', 'deleted', 'Media', $id, ['name' => $name]);
    }

    public function createFolder(MediaFolderInput $input): MediaFolder
    {
        $folder = new MediaFolder()->setName($input->name);

        if (null !== $input->parentId) {
            $parent = $this->folderRepository->find($input->parentId);
            if (null === $parent) {
                throw new InvalidArgumentException($this->translator->trans('admin.media.errors.parent_folder_not_found', ['{id}' => $input->parentId]));
            }

            $folder->setParent($parent);
        }

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $folder;
    }

    public function updateFolder(MediaFolder $folder, MediaFolderInput $input): void
    {
        $folder->setName($input->name);

        $newParent = null;
        if (null !== $input->parentId) {
            $newParent = $this->folderRepository->find($input->parentId);
            if (null === $newParent) {
                throw new InvalidArgumentException($this->translator->trans('admin.media.errors.parent_folder_not_found', ['{id}' => $input->parentId]));
            }

            if ($newParent === $folder || $newParent->isDescendantOf($folder)) {
                throw new InvalidArgumentException($this->translator->trans('admin.media.errors.folder_self_nested'));
            }
        }

        $folder->setParent($newParent);

        $this->entityManager->flush();
    }

    public function deleteFolder(MediaFolder $folder): void
    {
        // FK onDelete: SET NULL on media/media_folders children → they bubble up to root.
        $this->entityManager->remove($folder);
        $this->entityManager->flush();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            $media = $this->mediaRepository->find($id);
            $media?->setPosition($position);
        }

        $this->entityManager->flush();
    }

    public function bulkDelete(array $ids): void
    {
        foreach ($ids as $id) {
            $media = $this->mediaRepository->find($id);
            if (null === $media) {
                continue;
            }

            $filePath = Path::join($this->uploadDir, $media->getPath());
            $this->filesystem->remove($filePath);

            $this->variantGenerator->deleteVariants($media->getVariants());
            $this->entityManager->remove($media);
        }

        $this->entityManager->flush();
    }

    public function bulkMove(array $ids, ?MediaFolder $folder): void
    {
        foreach ($ids as $id) {
            $media = $this->mediaRepository->find($id);
            $media?->setFolder($folder);
        }

        $this->entityManager->flush();
    }

    public function crop(Media $media, int $x, int $y, int $width, int $height): void
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
        $x = max(0, min($x, $sourceW - 1));
        $y = max(0, min($y, $sourceH - 1));
        $width = max(1, min($width, $sourceW - $x));
        $height = max(1, min($height, $sourceH - $y));

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
            'name' => $media->getOriginalName(),
            'crop' => ['x' => $x, 'y' => $y, 'w' => $width, 'h' => $height],
        ]);
    }
}
