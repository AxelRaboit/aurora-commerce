<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm\PdfTemplate\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Aurora\Module\Welding\PdfTemplate\Serializer\WeldingPdfTemplateSerializerInterface;
use Aurora\Module\Welding\PdfTemplate\View\WeldingPdfTemplatesViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class WeldingPdfTemplatesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsTemplatesAndPaths(): void
    {
        $repo = $this->createStub(WeldingPdfTemplateRepository::class);
        $repo->method('findPaginated')->willReturn([
            'items' => [new WeldingPdfTemplate()],
            'total' => 1,
            'page' => 1,
            'totalPages' => 1,
        ]);

        $serializer = $this->createStub(WeldingPdfTemplateSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $pagination = new PaginationRequest(1, 20, 'contract');
        $view = (new WeldingPdfTemplatesViewBuilder($repo, $serializer, $urlGenerator))->indexView($pagination);

        self::assertArrayHasKey('templates', $view);
        self::assertSame('contract', $view['search']);
        self::assertArrayHasKey('createPath', $view);
        self::assertArrayHasKey('mediaPickerPath', $view);
    }

    public function testBuildListPayloadAcceptsStatusFilter(): void
    {
        $repo = $this->createStub(WeldingPdfTemplateRepository::class);
        $repo->method('findPaginated')->willReturn([
            'items' => [new WeldingPdfTemplate(), new WeldingPdfTemplate()],
            'total' => 2,
            'page' => 1,
            'totalPages' => 1,
        ]);

        $serializer = $this->createStub(WeldingPdfTemplateSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        $payload = (new WeldingPdfTemplatesViewBuilder($repo, $serializer, $urlGenerator))
            ->buildListPayload(new PaginationRequest(1, 20, null), 'active');

        self::assertCount(2, $payload['items']);
        self::assertSame(2, $payload['total']);
    }
}
