<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Manager;

use Aurora\Core\Media\Contract\MediaManagerInterface;
use Aurora\Core\Media\DTO\MediaFolderInput;
use Aurora\Core\Media\DTO\MediaInput;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Aurora\Core\Media\Repository\MediaFolderRepository;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Media\Service\ImageVariantGenerator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadDir,
    ) {}

    public function upload(UploadedFile $file, ?MediaFolder $folder = null): Media
    {
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $clientName = $file->getClientOriginalName();

        $safeFilename = $this->slugger->slug(pathinfo($clientName, PATHINFO_FILENAME))->lower();
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);

        $file->move($this->uploadDir, $newFilename);

        [$width, $height] = @getimagesize(sprintf('%s/%s', $this->uploadDir, $newFilename)) ?: [null, null];

        $media = new Media();
        $media->setFilename($newFilename);
        $media->setOriginalName($clientName);
        $media->setMimeType($mimeType);
        $media->setSize($size);
        $media->setPath($newFilename);
        $media->setWidth($width);
        $media->setHeight($height);
        $media->setFolder($folder);
        $media->setVariants($this->variantGenerator->generate($newFilename, (string) $mimeType));

        $this->entityManager->persist($media);
        $this->entityManager->flush();

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
    }

    public function move(Media $media, ?MediaFolder $folder): void
    {
        $media->setFolder($folder);
        $this->entityManager->flush();
    }

    public function delete(Media $media): void
    {
        $filePath = sprintf('%s/%s', $this->uploadDir, $media->getPath());
        if (is_file($filePath)) {
            @unlink($filePath);
        }

        $this->variantGenerator->deleteVariants($media->getVariants());

        $this->entityManager->remove($media);
        $this->entityManager->flush();
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
}
