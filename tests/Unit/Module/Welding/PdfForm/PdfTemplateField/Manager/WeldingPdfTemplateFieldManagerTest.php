<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm\PdfTemplateField\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use Aurora\Module\Welding\PdfTemplateField\Dto\WeldingPdfTemplateFieldInputInterface;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateField;
use Aurora\Module\Welding\PdfTemplateField\Manager\WeldingPdfTemplateFieldManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WeldingPdfTemplateFieldManagerTest extends TestCase
{
    private function makeInput(): WeldingPdfTemplateFieldInputInterface
    {
        $input = $this->createStub(WeldingPdfTemplateFieldInputInterface::class);
        $input->method('getPdfFieldName')->willReturn('contract_name');
        $input->method('getLabel')->willReturn('Name');
        $input->method('getFieldType')->willReturn(WeldingPdfFieldTypeEnum::Text);
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
            ->with('welding', 'field.updated', 'WeldingPdfTemplateField', self::anything(), self::anything());

        $template = (new WeldingPdfTemplate())->setName('Contract');
        $field = (new WeldingPdfTemplateField())->setTemplate($template);

        (new WeldingPdfTemplateFieldManager($em, $audit))->update($field, $this->makeInput());

        self::assertSame('contract_name', $field->getPdfFieldName());
        self::assertSame('Name', $field->getLabel());
        self::assertSame(WeldingPdfFieldTypeEnum::Text, $field->getFieldType());
        self::assertSame('user.name', $field->getMappingKey());
        self::assertSame('N/A', $field->getDefaultValue());
        self::assertSame(3, $field->getPosition());
    }

    public function testDeleteRemovesAndAudits(): void
    {
        $template = (new WeldingPdfTemplate())->setName('Template');
        $field = (new WeldingPdfTemplateField())->setTemplate($template)->setPdfFieldName('f')->setLabel('L');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($field);
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('welding', 'field.deleted', 'WeldingPdfTemplateField', self::anything(), self::anything());

        (new WeldingPdfTemplateFieldManager($em, $audit))->delete($field);
    }
}
