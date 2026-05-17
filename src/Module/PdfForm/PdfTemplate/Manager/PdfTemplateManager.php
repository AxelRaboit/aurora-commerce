<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Core\Media\Library\Repository\MediaRepository;
use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Aurora\Module\PdfForm\PdfTemplate\Dto\PdfTemplateInputInterface;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateField;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;
use Aurora\Module\PdfForm\Service\PdfManipulatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

#[AsAlias(PdfTemplateManagerInterface::class)]
class PdfTemplateManager implements PdfTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MediaRepository $mediaRepository,
        protected readonly PdfManipulatorInterface $pdfManipulator,
        protected readonly AuditLogger $auditLogger,
        #[Autowire('%app.upload_dir%')]
        protected readonly string $uploadDir,
    ) {}

    public function create(PdfTemplateInputInterface $input): PdfTemplateInterface
    {
        $template = $this->createPdfTemplate();
        $this->applyInput($template, $input);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $this->auditCreated($template);

        return $template;
    }

    public function update(PdfTemplateInterface $template, PdfTemplateInputInterface $input): void
    {
        $this->applyInput($template, $input);
        $this->entityManager->flush();

        $this->auditUpdated($template);
    }

    public function delete(PdfTemplateInterface $template): void
    {
        $this->auditDeleted($template);

        $this->entityManager->remove($template);
        $this->entityManager->flush();
    }

    public function detectAndSyncFields(PdfTemplateInterface $template): array
    {
        $file = $template->getFile();
        if (!$file instanceof MediaInterface) {
            return [];
        }

        $absolutePath = Path::join($this->uploadDir, $file->getPath());
        if (!file_exists($absolutePath)) {
            return [];
        }

        $detectedFields = $this->pdfManipulator->detectFields($absolutePath);

        $existingByName = [];
        foreach ($template->getFields() as $field) {
            $existingByName[$field->getPdfFieldName()] = $field;
        }

        $position = 0;
        foreach ($detectedFields as $detected) {
            if (isset($existingByName[$detected['name']])) {
                $existingByName[$detected['name']]->setPosition($position);
                unset($existingByName[$detected['name']]);
            } else {
                $field = $this->createPdfTemplateField();
                $field->setTemplate($template);
                $field->setPdfFieldName($detected['name']);
                $field->setLabel($detected['name']);
                $field->setFieldType(PdfFieldTypeEnum::tryFrom($detected['type']) ?? PdfFieldTypeEnum::Text);
                $field->setPosition($position);
                $this->entityManager->persist($field);
            }

            ++$position;
        }

        foreach ($existingByName as $orphan) {
            $this->entityManager->remove($orphan);
        }

        $this->entityManager->flush();
        $this->entityManager->refresh($template);

        return $detectedFields;
    }

    protected function createPdfTemplate(): PdfTemplateInterface
    {
        return new PdfTemplate();
    }

    protected function createPdfTemplateField(): PdfTemplateFieldInterface
    {
        return new PdfTemplateField();
    }

    protected function applyInput(PdfTemplateInterface $template, PdfTemplateInputInterface $input): void
    {
        $template->setName($input->getName());
        $template->setDescription($input->getDescription());
        $template->setStatus($input->getStatus());
        $template->setFile(null !== $input->getFileId() ? $this->mediaRepository->find($input->getFileId()) : null);
        $template->setFlattenOnGenerate($input->isFlattenOnGenerate());
        $template->setRequiresSignature($input->isRequiresSignature());
    }

    protected function auditCreated(PdfTemplateInterface $template): void
    {
        $this->auditLogger->log('pdfform', 'template.created', 'PdfTemplate', $template->getId(), $this->auditPayload($template));
    }

    protected function auditUpdated(PdfTemplateInterface $template): void
    {
        $this->auditLogger->log('pdfform', 'template.updated', 'PdfTemplate', $template->getId(), $this->auditPayload($template));
    }

    protected function auditDeleted(PdfTemplateInterface $template): void
    {
        $this->auditLogger->log('pdfform', 'template.deleted', 'PdfTemplate', $template->getId(), $this->auditPayload($template));
    }

    protected function auditPayload(PdfTemplateInterface $template): array
    {
        return ['name' => $template->getName(), 'status' => $template->getStatus()->value];
    }
}
