<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\PdfForm\Enum\PdfDocumentStatusEnum;
use Aurora\Module\PdfForm\Setting\PdfFormSettingEnum;
use Aurora\Module\PdfForm\PdfDocument\Dto\PdfDocumentInputInterface;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocument;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;
use Aurora\Module\PdfForm\PdfDocument\Service\PdfDocumentStorage;
use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Aurora\Module\PdfForm\Service\PdfManipulatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Throwable;

#[AsAlias(PdfDocumentManagerInterface::class)]
class PdfDocumentManager implements PdfDocumentManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly PdfTemplateRepository $templateRepository,
        protected readonly PdfManipulatorInterface $pdfManipulator,
        protected readonly PdfDocumentStorage $storage,
        protected readonly SettingRepository $settingRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly AuditLogger $auditLogger,
        #[Autowire('%app.upload_dir%')]
        protected readonly string $uploadDir,
    ) {}

    public function generate(PdfDocumentInputInterface $input): PdfDocumentInterface
    {
        $template = $this->templateRepository->find($input->getTemplateId());

        $document = $this->createPdfDocument();
        $prefix = $this->settingRepository->getOrDefault(PdfFormSettingEnum::DocumentPrefix);
        $document->setReference($this->sequenceGenerator->next($prefix));
        $document->setTemplate($template);
        $document->setLabel($input->getLabel());
        $document->setFieldValues($input->getFieldValues());

        if (null !== $template && null !== $template->getFile() && $this->pdfManipulator->isAvailable()) {
            $absoluteTemplatePath = Path::join($this->uploadDir, $template->getFile()->getPath());
            $filePath = $this->generateAndStore($absoluteTemplatePath, $input->getFieldValues(), $document->getReference() ?? 'doc', $template->isFlattenOnGenerate());

            if (null !== $filePath) {
                $document->setFilePath($filePath);
                $document->setStatus(PdfDocumentStatusEnum::Generated);
            }
        }

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->auditCreated($document);

        return $document;
    }

    public function delete(PdfDocumentInterface $document): void
    {
        $this->auditDeleted($document);
        $this->storage->delete($document);
        $this->entityManager->remove($document);
        $this->entityManager->flush();
    }

    public function getAbsolutePath(PdfDocumentInterface $document): ?string
    {
        return $this->storage->absolutePath($document);
    }

    protected function createPdfDocument(): PdfDocumentInterface
    {
        return new PdfDocument();
    }

    /** @param array<string, string> $fieldValues */
    protected function generateAndStore(string $templatePath, array $fieldValues, string $reference, bool $flatten): ?string
    {
        $outputPath = tempnam(sys_get_temp_dir(), self::TMP_OUTPUT_PREFIX).'.pdf';

        try {
            $this->pdfManipulator->fill($templatePath, $fieldValues, $outputPath, $flatten);

            if (!file_exists($outputPath) || 0 === filesize($outputPath)) {
                return null;
            }

            return $this->storage->store($outputPath, $reference);
        } catch (Throwable) {
            return null;
        } finally {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    private const string TMP_OUTPUT_PREFIX = 'aurora_pdfform_out_';

    protected function auditCreated(PdfDocumentInterface $document): void
    {
        $this->auditLogger->log('pdfform', 'document.generated', 'PdfDocument', $document->getId(), $this->auditPayload($document));
    }

    protected function auditDeleted(PdfDocumentInterface $document): void
    {
        $this->auditLogger->log('pdfform', 'document.deleted', 'PdfDocument', $document->getId(), $this->auditPayload($document));
    }

    protected function auditPayload(PdfDocumentInterface $document): array
    {
        return [
            'reference' => $document->getReference(),
            'template' => $document->getTemplate()?->getName(),
            'label' => $document->getLabel(),
        ];
    }
}
