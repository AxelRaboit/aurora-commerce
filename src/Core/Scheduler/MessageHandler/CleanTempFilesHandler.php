<?php

declare(strict_types=1);

namespace Aurora\Core\Scheduler\MessageHandler;

use Aurora\Core\Scheduler\Message\CleanTempFilesMessage;
use Aurora\Module\Welding\PdfDocument\Repository\WeldingPdfDocumentRepository;
use FilesystemIterator;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Cleans up orphaned temporary files left by Aurora processes.
 *
 * Covers:
 *   - /tmp/aurora_welding_pdf_* (XFDF + JSON values from pdftk fill — crash orphans)
 *   - /tmp/aurora_ssh_*         (SSH key files from MountPoint tunnels — crash orphans)
 *   - var/uploads/welding/pdf-documents/**\/*.pdf
 *       (generated PDFs whose WeldingPdfDocument entity was deleted)
 */
#[AsMessageHandler]
final readonly class CleanTempFilesHandler
{
    /** Files older than this are considered orphans. */
    private const int TMP_MAX_AGE_MINUTES = 30;

    /** Prefixes of temporary files Aurora creates in sys_get_temp_dir(). */
    private const array TMP_PREFIXES = [
        'aurora_welding_pdf_xfdf_',
        'aurora_welding_pdf_values_',
        'aurora_ssh_',
    ];

    public function __construct(
        private WeldingPdfDocumentRepository $pdfDocumentRepository,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public function __invoke(CleanTempFilesMessage $message): void
    {
        $tmpCleaned = $this->cleanTmpFiles();
        $pdfCleaned = $this->cleanOrphanPdfFiles();

        if ($tmpCleaned > 0 || $pdfCleaned > 0) {
            $this->logger->info('CleanTempFiles: removed {tmp} tmp file(s), {pdf} orphan PDF(s).', [
                'tmp' => $tmpCleaned,
                'pdf' => $pdfCleaned,
            ]);
        }
    }

    private function cleanTmpFiles(): int
    {
        $cutoff = time() - (self::TMP_MAX_AGE_MINUTES * 60);
        $tmpDir = sys_get_temp_dir();
        $removed = 0;

        foreach (self::TMP_PREFIXES as $prefix) {
            $pattern = $tmpDir.DIRECTORY_SEPARATOR.$prefix.'*';
            foreach (glob($pattern) ?: [] as $file) {
                if (is_file($file) && filemtime($file) < $cutoff) {
                    @unlink($file);
                    ++$removed;
                }
            }
        }

        return $removed;
    }

    private function cleanOrphanPdfFiles(): int
    {
        $storageDir = $this->projectDir.'/var/uploads/welding/pdf-documents';
        if (!is_dir($storageDir)) {
            return 0;
        }

        // Collect all file paths stored in the database
        $knownPaths = $this->pdfDocumentRepository->findAllFilePaths();
        $knownSet = array_flip($knownPaths);

        $removed = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($storageDir, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ('pdf' !== $file->getExtension()) {
                continue;
            }

            // Relative path within the welding PDF storage dir (e.g. 2026/05/PDF-000001.pdf)
            $relativePath = mb_ltrim(str_replace($storageDir, '', $file->getPathname()), DIRECTORY_SEPARATOR);

            if (!isset($knownSet[$relativePath])) {
                @unlink($file->getPathname());
                ++$removed;
            }
        }

        return $removed;
    }
}
