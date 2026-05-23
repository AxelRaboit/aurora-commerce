<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\Enum\WeldingPdfDocumentStatusEnum;
use Aurora\Module\Welding\PdfDocument\Dto\WeldingPdfDocumentInputInterface;
use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocument;
use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocumentInterface;
use Aurora\Module\Welding\PdfDocument\Service\WeldingPdfDocumentStorage;
use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Aurora\Module\Welding\Service\WeldingPdfManipulatorInterface;
use Aurora\Module\Welding\Setting\WeldingSettingEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Throwable;

#[AsAlias(WeldingPdfDocumentManagerInterface::class)]
class WeldingPdfDocumentManager implements WeldingPdfDocumentManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WeldingPdfTemplateRepository $templateRepository,
        protected readonly WeldingPdfManipulatorInterface $pdfManipulator,
        protected readonly WeldingPdfDocumentStorage $storage,
        protected readonly SettingRepository $settingRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly AuditLogger $auditLogger,
        #[Autowire('%app.upload_dir%')]
        protected readonly string $uploadDir,
    ) {}

    public function generate(WeldingPdfDocumentInputInterface $input): WeldingPdfDocumentInterface
    {
        $template = $this->templateRepository->find($input->getTemplateId());

        $document = $this->createPdfDocument();
        $prefix = $this->settingRepository->getOrDefault(WeldingSettingEnum::PdfDocumentPrefix);
        $document->setReference($this->sequenceGenerator->next($prefix));
        $document->setTemplate($template);
        $document->setLabel($input->getLabel());
        $document->setFieldValues($input->getFieldValues());
        $document->setContextType($input->getContextType());
        $document->setContextId($input->getContextId());

        if (null !== $template && null !== $template->getFile() && $this->pdfManipulator->isAvailable()) {
            $absoluteTemplatePath = Path::join($this->uploadDir, $template->getFile()->getPath());
            $filePath = $this->generateAndStore($absoluteTemplatePath, $input->getFieldValues(), $document->getReference() ?? 'doc', $template->isFlattenOnGenerate());

            if (null !== $filePath) {
                $document->setFilePath($filePath);
                $document->setStatus(WeldingPdfDocumentStatusEnum::Generated);
            }
        }

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->auditCreated($document);

        return $document;
    }

    public function delete(WeldingPdfDocumentInterface $document): void
    {
        $this->auditDeleted($document);
        $this->storage->delete($document);
        $this->entityManager->remove($document);
        $this->entityManager->flush();
    }

    public function getAbsolutePath(WeldingPdfDocumentInterface $document): ?string
    {
        return $this->storage->absolutePath($document);
    }

    protected function createPdfDocument(): WeldingPdfDocumentInterface
    {
        return new WeldingPdfDocument();
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

    protected function auditCreated(WeldingPdfDocumentInterface $document): void
    {
        $this->auditLogger->log('welding', 'document.generated', 'WeldingPdfDocument', $document->getId(), $this->auditPayload($document));
    }

    protected function auditDeleted(WeldingPdfDocumentInterface $document): void
    {
        $this->auditLogger->log('welding', 'document.deleted', 'WeldingPdfDocument', $document->getId(), $this->auditPayload($document));
    }

    protected function auditPayload(WeldingPdfDocumentInterface $document): array
    {
        return [
            'reference' => $document->getReference(),
            'template' => $document->getTemplate()?->getName(),
            'label' => $document->getLabel(),
        ];
    }
}
