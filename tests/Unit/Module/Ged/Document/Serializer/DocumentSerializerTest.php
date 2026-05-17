<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\Document\Serializer;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Serializer\DocumentSerializer;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Aurora\Tests\Concern\CreatesStorageUrlGenerators;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DocumentSerializerTest extends TestCase
{
    use CreatesStorageUrlGenerators;

    private TranslatorInterface $translator;
    private DocumentSerializer $serializer;

    protected function setUp(): void
    {
        $this->translator = $this->createStub(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnArgument(0);
        $this->serializer = new DocumentSerializer($this->translator, $this->makeMediaUrlGenerator());
    }

    private function makeDocument(
        int $id = 1,
        string $reference = 'GED-001',
        string $title = 'Annual Report',
        ?string $description = 'Year 2025',
        DocumentStatusEnum $status = DocumentStatusEnum::Published,
        ?DocumentCategoryInterface $category = null,
        ?MediaInterface $file = null,
        array $tags = [],
        ?DocumentFolderInterface $folder = null,
        string $createdAt = '2025-01-01T00:00:00+00:00',
        string $updatedAt = '2025-06-01T00:00:00+00:00',
    ): DocumentInterface {
        $document = $this->createStub(DocumentInterface::class);
        $document->method('getId')->willReturn($id);
        $document->method('getReference')->willReturn($reference);
        $document->method('getTitle')->willReturn($title);
        $document->method('getDescription')->willReturn($description);
        $document->method('getStatus')->willReturn($status);
        $document->method('getCategory')->willReturn($category);
        $document->method('getFile')->willReturn($file);
        $document->method('getTags')->willReturn(new ArrayCollection($tags));
        $document->method('getFolder')->willReturn($folder);
        $document->method('getCreatedAt')->willReturn(new DateTimeImmutable($createdAt));
        $document->method('getUpdatedAt')->willReturn(new DateTimeImmutable($updatedAt));

        return $document;
    }

    public function testSerializeMinimalDocumentReturnsAllKeys(): void
    {
        $result = $this->serializer->serialize($this->makeDocument());

        self::assertSame(1, $result['id']);
        self::assertSame('GED-001', $result['reference']);
        self::assertSame('Annual Report', $result['title']);
        self::assertSame('Year 2025', $result['description']);
        self::assertSame(DocumentStatusEnum::Published->value, $result['status']);
        self::assertNull($result['categoryId']);
        self::assertNull($result['categoryName']);
        self::assertNull($result['fileId']);
        self::assertNull($result['fileName']);
        self::assertNull($result['fileUrl']);
        self::assertNull($result['fileMime']);
        self::assertNull($result['fileSize']);
        self::assertSame([], $result['tagIds']);
        self::assertSame([], $result['tags']);
        self::assertNull($result['folderId']);
        self::assertNull($result['folderName']);
        self::assertSame('2025-01-01T00:00:00+00:00', $result['createdAt']);
        self::assertSame('2025-06-01T00:00:00+00:00', $result['updatedAt']);
    }

    public function testSerializeStatusLabelCallsTranslator(): void
    {
        $result = $this->serializer->serialize($this->makeDocument(status: DocumentStatusEnum::Draft));

        self::assertSame(DocumentStatusEnum::Draft->getLabelKey(), $result['statusLabel']);
    }

    public function testSerializeWithCategoryIncludesCategoryData(): void
    {
        $category = $this->createStub(DocumentCategoryInterface::class);
        $category->method('getId')->willReturn(42);
        $category->method('getName')->willReturn('Legal');

        $result = $this->serializer->serialize($this->makeDocument(category: $category));

        self::assertSame(42, $result['categoryId']);
        self::assertSame('Legal', $result['categoryName']);
    }

    public function testSerializeWithFileIncludesFileData(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $file->method('getId')->willReturn(10);
        $file->method('getFileName')->willReturn('report.pdf');
        $file->method('getPath')->willReturn('report.pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(98765);

        $result = $this->serializer->serialize($this->makeDocument(file: $file));

        self::assertSame(10, $result['fileId']);
        self::assertSame('report.pdf', $result['fileName']);
        self::assertSame('/uploads/report.pdf', $result['fileUrl']);
        self::assertSame('application/pdf', $result['fileMime']);
        self::assertSame(98765, $result['fileSize']);
    }

    public function testSerializeWithTagsMapsIdsAndFullData(): void
    {
        $tag1 = $this->createStub(DocumentTagInterface::class);
        $tag1->method('getId')->willReturn(7);
        $tag1->method('getName')->willReturn('Urgent');
        $tag1->method('getColor')->willReturn('#f00');

        $tag2 = $this->createStub(DocumentTagInterface::class);
        $tag2->method('getId')->willReturn(8);
        $tag2->method('getName')->willReturn('Draft');
        $tag2->method('getColor')->willReturn(null);

        $result = $this->serializer->serialize($this->makeDocument(tags: [$tag1, $tag2]));

        self::assertSame([7, 8], $result['tagIds']);
        self::assertSame([
            ['id' => 7, 'name' => 'Urgent', 'color' => '#f00'],
            ['id' => 8, 'name' => 'Draft', 'color' => null],
        ], $result['tags']);
    }

    public function testSerializeWithFolderIncludesFolderData(): void
    {
        $folder = $this->createStub(DocumentFolderInterface::class);
        $folder->method('getId')->willReturn(3);
        $folder->method('getName')->willReturn('Archive');

        $result = $this->serializer->serialize($this->makeDocument(folder: $folder));

        self::assertSame(3, $result['folderId']);
        self::assertSame('Archive', $result['folderName']);
    }
}
