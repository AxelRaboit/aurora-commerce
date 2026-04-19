<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\MediaManagerInterface;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsAlias(MediaManagerInterface::class)]
final readonly class MediaManager implements MediaManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadDir,
    ) {}

    public function upload(UploadedFile $file): Media
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

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }
}
