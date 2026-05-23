<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Attachment\Service;

use Aurora\Core\Storage\BinaryFileServer;
use Aurora\Module\PersonalFinance\Transaction\Attachment\Enum\PersonalFinanceAttachmentMimeTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

#[AsAlias(PersonalFinanceTransactionAttachmentServiceInterface::class)]
class PersonalFinanceTransactionAttachmentService implements PersonalFinanceTransactionAttachmentServiceInterface
{
    public const int MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Domain allowlist of MIME types accepted as a receipt. Matches
     * Spendly: common raster images + PDF. SVG excluded (XSS risk).
     *
     * @var list<PersonalFinanceAttachmentMimeTypeEnum>
     */
    private const array ALLOWED_MIME_TYPES = [
        PersonalFinanceAttachmentMimeTypeEnum::Jpeg,
        PersonalFinanceAttachmentMimeTypeEnum::Jpg,
        PersonalFinanceAttachmentMimeTypeEnum::Png,
        PersonalFinanceAttachmentMimeTypeEnum::Webp,
        PersonalFinanceAttachmentMimeTypeEnum::Pdf,
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads/personal-finance/transactions')]
        protected readonly string $storageDir,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly BinaryFileServer $binaryFileServer,
        protected readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    public function attach(PersonalFinanceTransactionInterface $transaction, UploadedFile $file): void
    {
        $transactionId = $transaction->getId();
        if (null === $transactionId) {
            throw new RuntimeException('Cannot attach to a transaction that has not been persisted yet.');
        }

        $size = $file->getSize();
        if (false !== $size && $size > self::MAX_FILE_SIZE) {
            throw new FileException(sprintf('Attachment exceeds max size of %d bytes.', self::MAX_FILE_SIZE));
        }

        $mime = $file->getMimeType() ?? '';
        $mimeEnum = PersonalFinanceAttachmentMimeTypeEnum::tryFrom($mime);
        if (null === $mimeEnum || !in_array($mimeEnum, self::ALLOWED_MIME_TYPES, true)) {
            throw new FileException(sprintf('Unsupported MIME type "%s".', $mime));
        }

        if ($transaction->hasAttachment()) {
            $this->removeFileForTransaction($transaction);
        }

        $filename = sprintf('%s.%s', Uuid::v4()->toRfc4122(), $mimeEnum->extension());
        $relativeDir = (string) $transactionId;
        $absoluteDir = Path::join($this->storageDir, $relativeDir);

        $this->filesystem->mkdir($absoluteDir, 0o755);
        $file->move($absoluteDir, $filename);

        $relativePath = $relativeDir.'/'.$filename;
        $originalName = $this->sanitizeOriginalName($file->getClientOriginalName());

        $transaction->setAttachmentPath($relativePath);
        $transaction->setAttachmentOriginalName($originalName);

        $this->entityManager->flush();
    }

    public function detach(PersonalFinanceTransactionInterface $transaction): void
    {
        if (!$transaction->hasAttachment()) {
            return;
        }

        $this->removeFileForTransaction($transaction);
        $transaction->setAttachmentPath(null);
        $transaction->setAttachmentOriginalName(null);

        $this->entityManager->flush();
    }

    public function serve(PersonalFinanceTransactionInterface $transaction): BinaryFileResponse
    {
        $relative = $transaction->getAttachmentPath();
        if (null === $relative) {
            throw new RuntimeException('Transaction has no attachment.');
        }

        return $this->binaryFileServer->serve(
            absolutePath: $this->binaryFileServer->path($this->storageDir, $relative),
            allowedRoot: $this->storageDir,
            downloadName: $transaction->getAttachmentOriginalName() ?? basename($relative),
        );
    }

    public function purgeDirectory(int $transactionId): void
    {
        $dir = Path::join($this->storageDir, (string) $transactionId);
        if ($this->filesystem->exists($dir)) {
            $this->filesystem->remove($dir);
        }
    }

    protected function removeFileForTransaction(PersonalFinanceTransactionInterface $transaction): void
    {
        $relative = $transaction->getAttachmentPath();
        if (null === $relative) {
            return;
        }

        $abs = Path::join($this->storageDir, $relative);
        if ($this->filesystem->exists($abs)) {
            $this->filesystem->remove($abs);
        }
    }

    protected function sanitizeOriginalName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\w.\- ]+/u', '_', $name) ?? '';
        $name = mb_substr($name, 0, 255);

        return '' !== $name ? $name : 'attachment';
    }
}
