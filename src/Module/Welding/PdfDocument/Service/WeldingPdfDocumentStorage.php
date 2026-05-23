<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Service;

use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocumentInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final readonly class WeldingPdfDocumentStorage
{
    private const string STORAGE_SUBDIR = 'pdfform';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public function storageDir(): string
    {
        return Path::join($this->projectDir, 'var', self::STORAGE_SUBDIR);
    }

    public function absolutePath(WeldingPdfDocumentInterface $document): ?string
    {
        if (null === $document->getFilePath()) {
            return null;
        }

        return Path::join($this->storageDir(), $document->getFilePath());
    }

    public function fileExists(WeldingPdfDocumentInterface $document): bool
    {
        $path = $this->absolutePath($document);

        return null !== $path && file_exists($path);
    }

    /** @return string Relative path within storage dir (e.g. 2026/05/PDF-000001.pdf) */
    public function store(string $sourcePath, string $reference): string
    {
        $now = new DateTimeImmutable();
        $year = $now->format('Y');
        $month = $now->format('m');
        $destDir = Path::join($this->storageDir(), $year, $month);

        $fs = new Filesystem();
        $fs->mkdir($destDir);

        $filename = $reference.'.pdf';
        $fs->copy($sourcePath, Path::join($destDir, $filename), true);

        return $year.'/'.$month.'/'.$filename;
    }

    public function delete(WeldingPdfDocumentInterface $document): void
    {
        $path = $this->absolutePath($document);
        if (null !== $path && file_exists($path)) {
            new Filesystem()->remove($path);
        }
    }
}
