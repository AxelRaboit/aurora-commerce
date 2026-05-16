<?php

declare(strict_types=1);

namespace Aurora\Core\User\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsAlias(UserProfilePhotoManagerInterface::class)]
class UserProfilePhotoManager implements UserProfilePhotoManagerInterface
{
    private const int MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly SluggerInterface $slugger,
        protected readonly AuditLogger $auditLogger,
        protected readonly Filesystem $filesystem,
        #[Autowire('%app.upload_dir%/profile-photos')]
        protected readonly string $uploadDir,
    ) {}

    public function upload(User $user, UploadedFile $file): void
    {
        $size = $file->getSize();
        if (false !== $size && $size > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException('backend.users.photo.errors.too_large');
        }

        $mimeType = $file->getMimeType();
        if (null === $mimeType || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('backend.users.photo.errors.invalid_type');
        }

        if (!$this->filesystem->exists($this->uploadDir)) {
            $this->filesystem->mkdir($this->uploadDir, 0o755);
        }

        $this->removeFile($user->getProfilePhotoPath());

        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $base = $this->slugger->slug((string) $user->getId())->lower();
        $newFilename = sprintf('%s-%s.%s', $base, uniqid(), $extension);

        $file->move($this->uploadDir, $newFilename);

        $user->setProfilePhotoPath($newFilename);
        $this->entityManager->flush();

        $this->auditLogger->log('users', 'photo_uploaded', 'User', $user->getId(), ['filename' => $newFilename]);
    }

    public function delete(User $user): void
    {
        $path = $user->getProfilePhotoPath();
        if (null === $path) {
            return;
        }

        $this->removeFile($path);
        $user->setProfilePhotoPath(null);
        $this->entityManager->flush();

        $this->auditLogger->log('users', 'photo_removed', 'User', $user->getId(), ['filename' => $path]);
    }

    private function removeFile(?string $relativePath): void
    {
        if (null === $relativePath) {
            return;
        }

        $absolute = Path::join($this->uploadDir, $relativePath);
        if ($this->filesystem->exists($absolute)) {
            $this->filesystem->remove($absolute);
        }
    }
}
