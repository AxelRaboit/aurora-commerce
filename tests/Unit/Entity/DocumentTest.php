<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTag;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Document())->getId());
    }

    public function testDefaultValues(): void
    {
        $document = new Document();

        self::assertNull($document->getReference());
        self::assertNull($document->getDescription());
        self::assertSame(DocumentStatusEnum::Draft, $document->getStatus());
        self::assertNull($document->getCategory());
        self::assertNull($document->getFile());
        self::assertNull($document->getFolder());
    }

    public function testTagsCollectionInitialized(): void
    {
        self::assertCount(0, (new Document())->getTags());
    }

    public function testTitleGetterAndSetter(): void
    {
        $document = (new Document())->setTitle('Annual Report');

        self::assertSame('Annual Report', $document->getTitle());
    }

    public function testStatusGetterAndSetter(): void
    {
        $document = (new Document())->setStatus(DocumentStatusEnum::Published);

        self::assertSame(DocumentStatusEnum::Published, $document->getStatus());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $document = (new Document())->setDescription('Yearly summary');

        self::assertSame('Yearly summary', $document->getDescription());

        $document->setDescription(null);
        self::assertNull($document->getDescription());
    }

    public function testCategoryGetterAndSetter(): void
    {
        $category = new DocumentCategory();
        $document = (new Document())->setCategory($category);

        self::assertSame($category, $document->getCategory());

        $document->setCategory(null);
        self::assertNull($document->getCategory());
    }

    public function testFileGetterAndSetter(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $document = (new Document())->setFile($file);

        self::assertSame($file, $document->getFile());
    }

    public function testFolderGetterAndSetter(): void
    {
        $folder = $this->createStub(DocumentFolderInterface::class);
        $document = (new Document())->setFolder($folder);

        self::assertSame($folder, $document->getFolder());
    }

    public function testAddAndRemoveTag(): void
    {
        $document = new Document();
        $tag = new DocumentTag();

        $document->addTag($tag);
        self::assertCount(1, $document->getTags());

        $document->addTag($tag);
        self::assertCount(1, $document->getTags(), 'duplicate ignored');

        $document->removeTag($tag);
        self::assertCount(0, $document->getTags());
    }

    public function testClearTags(): void
    {
        $document = new Document();
        $document->addTag(new DocumentTag())->addTag(new DocumentTag());

        self::assertCount(2, $document->getTags());

        $document->clearTags();

        self::assertCount(0, $document->getTags());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $document = (new Document())->setReference('DOC-001');

        self::assertSame('DOC-001', $document->getReference());
    }
}
