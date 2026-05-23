<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Aurora\Module\Welding\PdfTemplate\Dto\WeldingPdfTemplateInputInterface;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateField;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;
use Aurora\Module\Welding\Service\WeldingPdfManipulatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

#[AsAlias(WeldingPdfTemplateManagerInterface::class)]
class WeldingPdfTemplateManager implements WeldingPdfTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MediaRepository $mediaRepository,
        protected readonly WeldingPdfManipulatorInterface $pdfManipulator,
        protected readonly AuditLogger $auditLogger,
        #[Autowire('%app.upload_dir%')]
        protected readonly string $uploadDir,
    ) {}

    public function create(WeldingPdfTemplateInputInterface $input): WeldingPdfTemplateInterface
    {
        $template = $this->createPdfTemplate();
        $this->applyInput($template, $input);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $this->auditCreated($template);

        return $template;
    }

    public function update(WeldingPdfTemplateInterface $template, WeldingPdfTemplateInputInterface $input): void
    {
        $this->applyInput($template, $input);
        $this->entityManager->flush();

        $this->auditUpdated($template);
    }

    public function delete(WeldingPdfTemplateInterface $template): void
    {
        $this->auditDeleted($template);

        $this->entityManager->remove($template);
        $this->entityManager->flush();
    }

    public function detectAndSyncFields(WeldingPdfTemplateInterface $template): array
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
                $field->setFieldType(WeldingPdfFieldTypeEnum::tryFrom($detected['type']) ?? WeldingPdfFieldTypeEnum::Text);
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

    protected function createPdfTemplate(): WeldingPdfTemplateInterface
    {
        return new WeldingPdfTemplate();
    }

    protected function createPdfTemplateField(): WeldingPdfTemplateFieldInterface
    {
        return new WeldingPdfTemplateField();
    }

    protected function applyInput(WeldingPdfTemplateInterface $template, WeldingPdfTemplateInputInterface $input): void
    {
        $template->setName($input->getName());
        $template->setDescription($input->getDescription());
        $template->setStatus($input->getStatus());
        $template->setFile(null !== $input->getFileId() ? $this->mediaRepository->find($input->getFileId()) : null);
        $template->setFlattenOnGenerate($input->isFlattenOnGenerate());
        $template->setRequiresSignature($input->isRequiresSignature());
    }

    protected function auditCreated(WeldingPdfTemplateInterface $template): void
    {
        $this->auditLogger->log('welding', 'template.created', 'WeldingPdfTemplate', $template->getId(), $this->auditPayload($template));
    }

    protected function auditUpdated(WeldingPdfTemplateInterface $template): void
    {
        $this->auditLogger->log('welding', 'template.updated', 'WeldingPdfTemplate', $template->getId(), $this->auditPayload($template));
    }

    protected function auditDeleted(WeldingPdfTemplateInterface $template): void
    {
        $this->auditLogger->log('welding', 'template.deleted', 'WeldingPdfTemplate', $template->getId(), $this->auditPayload($template));
    }

    protected function auditPayload(WeldingPdfTemplateInterface $template): array
    {
        return ['name' => $template->getName(), 'status' => $template->getStatus()->value];
    }
}
