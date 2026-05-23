<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\PdfTemplateField\Dto\WeldingPdfTemplateFieldInputInterface;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingPdfTemplateFieldManagerInterface::class)]
class WeldingPdfTemplateFieldManager implements WeldingPdfTemplateFieldManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function update(WeldingPdfTemplateFieldInterface $field, WeldingPdfTemplateFieldInputInterface $input): void
    {
        $this->applyInput($field, $input);
        $this->entityManager->flush();
        $this->auditUpdated($field);
    }

    public function delete(WeldingPdfTemplateFieldInterface $field): void
    {
        $this->auditDeleted($field);
        $this->entityManager->remove($field);
        $this->entityManager->flush();
    }

    protected function applyInput(WeldingPdfTemplateFieldInterface $field, WeldingPdfTemplateFieldInputInterface $input): void
    {
        $field->setPdfFieldName($input->getPdfFieldName());
        $field->setLabel($input->getLabel());
        $field->setFieldType($input->getFieldType());
        $field->setMappingKey($input->getMappingKey());
        $field->setDefaultValue($input->getDefaultValue());
        $field->setPosition($input->getPosition());
    }

    protected function auditUpdated(WeldingPdfTemplateFieldInterface $field): void
    {
        $this->auditLogger->log('welding', 'field.updated', 'WeldingPdfTemplateField', $field->getId(), $this->auditPayload($field));
    }

    protected function auditDeleted(WeldingPdfTemplateFieldInterface $field): void
    {
        $this->auditLogger->log('welding', 'field.deleted', 'WeldingPdfTemplateField', $field->getId(), $this->auditPayload($field));
    }

    protected function auditPayload(WeldingPdfTemplateFieldInterface $field): array
    {
        return [
            'pdfFieldName' => $field->getPdfFieldName(),
            'label' => $field->getLabel(),
            'template' => $field->getTemplate()->getName(),
        ];
    }
}
