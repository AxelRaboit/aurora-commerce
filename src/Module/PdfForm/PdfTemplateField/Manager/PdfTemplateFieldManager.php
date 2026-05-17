<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PdfForm\PdfTemplateField\Dto\PdfTemplateFieldInputInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PdfTemplateFieldManagerInterface::class)]
class PdfTemplateFieldManager implements PdfTemplateFieldManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function update(PdfTemplateFieldInterface $field, PdfTemplateFieldInputInterface $input): void
    {
        $this->applyInput($field, $input);
        $this->entityManager->flush();
        $this->auditUpdated($field);
    }

    public function delete(PdfTemplateFieldInterface $field): void
    {
        $this->auditDeleted($field);
        $this->entityManager->remove($field);
        $this->entityManager->flush();
    }

    protected function applyInput(PdfTemplateFieldInterface $field, PdfTemplateFieldInputInterface $input): void
    {
        $field->setPdfFieldName($input->getPdfFieldName());
        $field->setLabel($input->getLabel());
        $field->setFieldType($input->getFieldType());
        $field->setMappingKey($input->getMappingKey());
        $field->setDefaultValue($input->getDefaultValue());
        $field->setPosition($input->getPosition());
    }

    protected function auditUpdated(PdfTemplateFieldInterface $field): void
    {
        $this->auditLogger->log('pdfform', 'field.updated', 'PdfTemplateField', $field->getId(), $this->auditPayload($field));
    }

    protected function auditDeleted(PdfTemplateFieldInterface $field): void
    {
        $this->auditLogger->log('pdfform', 'field.deleted', 'PdfTemplateField', $field->getId(), $this->auditPayload($field));
    }

    protected function auditPayload(PdfTemplateFieldInterface $field): array
    {
        return [
            'pdfFieldName' => $field->getPdfFieldName(),
            'label' => $field->getLabel(),
            'template' => $field->getTemplate()->getName(),
        ];
    }
}
