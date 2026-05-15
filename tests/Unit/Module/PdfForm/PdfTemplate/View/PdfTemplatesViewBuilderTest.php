<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\PdfTemplate\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Aurora\Module\PdfForm\PdfTemplate\Serializer\PdfTemplateSerializerInterface;
use Aurora\Module\PdfForm\PdfTemplate\View\PdfTemplatesViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PdfTemplatesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsTemplatesAndPaths(): void
    {
        $repo = $this->createStub(PdfTemplateRepository::class);
        $repo->method('findPaginated')->willReturn([
            'items' => [new PdfTemplate()],
            'total' => 1,
            'page' => 1,
            'totalPages' => 1,
        ]);

        $serializer = $this->createStub(PdfTemplateSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $pagination = new PaginationRequest(1, 20, 'contract');
        $view = (new PdfTemplatesViewBuilder($repo, $serializer, $urlGenerator))->indexView($pagination);

        self::assertArrayHasKey('templates', $view);
        self::assertSame('contract', $view['search']);
        self::assertArrayHasKey('createPath', $view);
        self::assertArrayHasKey('mediaPickerPath', $view);
    }

    public function testBuildListPayloadAcceptsStatusFilter(): void
    {
        $repo = $this->createStub(PdfTemplateRepository::class);
        $repo->method('findPaginated')->willReturn([
            'items' => [new PdfTemplate(), new PdfTemplate()],
            'total' => 2,
            'page' => 1,
            'totalPages' => 1,
        ]);

        $serializer = $this->createStub(PdfTemplateSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        $payload = (new PdfTemplatesViewBuilder($repo, $serializer, $urlGenerator))
            ->buildListPayload(new PaginationRequest(1, 20, null), 'active');

        self::assertCount(2, $payload['items']);
        self::assertSame(2, $payload['total']);
    }
}
