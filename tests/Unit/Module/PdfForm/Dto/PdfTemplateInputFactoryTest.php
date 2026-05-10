<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Dto;

use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Aurora\Module\PdfForm\PdfTemplate\Dto\PdfTemplateInputFactory;
use PHPUnit\Framework\TestCase;

final class PdfTemplateInputFactoryTest extends TestCase
{
    private PdfTemplateInputFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new PdfTemplateInputFactory();
    }

    public function testFromArrayMapsName(): void
    {
        $input = $this->factory->fromArray(['name' => '  Mon template  ']);

        self::assertSame('Mon template', $input->getName());
    }

    public function testFromArrayMapsStatusEnum(): void
    {
        $input = $this->factory->fromArray(['name' => 'T', 'status' => 'active']);

        self::assertSame(PdfTemplateStatusEnum::Active, $input->getStatus());
    }

    public function testFromArrayStatusDefaultsToDraftOnUnknownValue(): void
    {
        $input = $this->factory->fromArray(['name' => 'T', 'status' => 'invalid']);

        self::assertSame(PdfTemplateStatusEnum::Draft, $input->getStatus());
    }

    public function testFromArrayMapsRequiresSignatureTrue(): void
    {
        $input = $this->factory->fromArray(['name' => 'T', 'requiresSignature' => true]);

        self::assertTrue($input->isRequiresSignature());
    }

    public function testFromArrayRequiresSignatureDefaultsFalseWhenAbsent(): void
    {
        $input = $this->factory->fromArray(['name' => 'T']);

        self::assertFalse($input->isRequiresSignature());
    }

    public function testFromArrayMapsRequiresSignatureFalseExplicitly(): void
    {
        $input = $this->factory->fromArray(['name' => 'T', 'requiresSignature' => false]);

        self::assertFalse($input->isRequiresSignature());
    }

    public function testFromArrayMapsFlattenOnGenerate(): void
    {
        $input = $this->factory->fromArray(['name' => 'T', 'flattenOnGenerate' => true]);

        self::assertTrue($input->isFlattenOnGenerate());
    }

    public function testFromArrayMapsFileId(): void
    {
        $input = $this->factory->fromArray(['name' => 'T', 'fileId' => '42']);

        self::assertSame(42, $input->getFileId());
    }

    public function testFromArrayFileIdIsNullWhenAbsent(): void
    {
        $input = $this->factory->fromArray(['name' => 'T']);

        self::assertNull($input->getFileId());
    }
}
