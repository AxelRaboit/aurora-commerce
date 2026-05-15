<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\PdfTemplateField\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplateField\Dto\PdfTemplateFieldInputInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateField;
use Aurora\Module\PdfForm\PdfTemplateField\Manager\PdfTemplateFieldManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PdfTemplateFieldManagerTest extends TestCase
{
    private function makeInput(): PdfTemplateFieldInputInterface
    {
        $input = $this->createStub(PdfTemplateFieldInputInterface::class);
        $input->method('getPdfFieldName')->willReturn('contract_name');
        $input->method('getLabel')->willReturn('Name');
        $input->method('getFieldType')->willReturn(PdfFieldTypeEnum::Text);
        $input->method('getMappingKey')->willReturn('user.name');
        $input->method('getDefaultValue')->willReturn('N/A');
        $input->method('getPosition')->willReturn(3);

        return $input;
    }

    public function testUpdateAppliesInputAndAudits(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('pdfform', 'field.updated', 'PdfTemplateField', self::anything(), self::anything());

        $template = (new PdfTemplate())->setName('Contract');
        $field = (new PdfTemplateField())->setTemplate($template);

        (new PdfTemplateFieldManager($em, $audit))->update($field, $this->makeInput());

        self::assertSame('contract_name', $field->getPdfFieldName());
        self::assertSame('Name', $field->getLabel());
        self::assertSame(PdfFieldTypeEnum::Text, $field->getFieldType());
        self::assertSame('user.name', $field->getMappingKey());
        self::assertSame('N/A', $field->getDefaultValue());
        self::assertSame(3, $field->getPosition());
    }

    public function testDeleteRemovesAndAudits(): void
    {
        $template = (new PdfTemplate())->setName('Template');
        $field = (new PdfTemplateField())->setTemplate($template)->setPdfFieldName('f')->setLabel('L');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($field);
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('pdfform', 'field.deleted', 'PdfTemplateField', self::anything(), self::anything());

        (new PdfTemplateFieldManager($em, $audit))->delete($field);
    }
}
