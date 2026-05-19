<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\Document\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ged\Document\Dto\DocumentInputInterface;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Entity\DocumentVersion;
use Aurora\Module\Ged\Document\Manager\DocumentManager;
use Aurora\Module\Ged\Document\Repository\DocumentVersionRepository;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Aurora\Module\Ged\DocumentFolder\Repository\DocumentFolderRepository;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Aurora\Module\Ged\DocumentTag\Repository\DocumentTagRepository;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class DocumentManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MediaRepository $mediaRepository;
    private DocumentCategoryRepository $categoryRepository;
    private DocumentTagRepository $tagRepository;
    private DocumentFolderRepository $folderRepository;
    private DocumentVersionRepository $versionRepository;
    private DocumentManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mediaRepository = $this->createMock(MediaRepository::class);
        $this->categoryRepository = $this->createMock(DocumentCategoryRepository::class);
        $this->tagRepository = $this->createMock(DocumentTagRepository::class);
        $this->folderRepository = $this->createMock(DocumentFolderRepository::class);
        $this->versionRepository = $this->createMock(DocumentVersionRepository::class);
        $this->versionRepository->method('getNextVersionNumber')->willReturn(1);

        $settingRepository = $this->createStub(SettingRepository::class);
        $settingRepository->method('getOrDefault')->willReturn(SequencePrefixEnum::GedDocument->value);

        $this->manager = new DocumentManager(
            $this->entityManager,
            $this->categoryRepository,
            $this->mediaRepository,
            $this->makeSequenceGenerator(),
            $settingRepository,
            $this->createStub(AuditLogger::class),
            $this->tagRepository,
            $this->folderRepository,
            $this->versionRepository,
        );
    }

    private function makeSequenceGenerator(): SequenceGenerator
    {
        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new SequenceGenerator($connection);
    }

    private function makeInput(
        string $title = 'Test Document',
        ?string $description = null,
        DocumentStatusEnum $status = DocumentStatusEnum::Draft,
        ?int $categoryId = null,
        ?int $fileId = null,
        array $tagIds = [],
        ?int $folderId = null,
    ): DocumentInputInterface {
        $input = $this->createStub(DocumentInputInterface::class);
        $input->method('getTitle')->willReturn($title);
        $input->method('getDescription')->willReturn($description);
        $input->method('getStatus')->willReturn($status);
        $input->method('getCategoryId')->willReturn($categoryId);
        $input->method('getFileId')->willReturn($fileId);
        $input->method('getTagIds')->willReturn($tagIds);
        $input->method('getFolderId')->willReturn($folderId);

        return $input;
    }

    private function captureDocument(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof Document) {
                    $captured = $entity;
                }
            }
        );
    }

    private function captureDocumentVersion(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof DocumentVersion) {
                    $captured = $entity;
                }
            }
        );
    }

    // --- create() ---

    public function testCreateSetsReferenceOnDocument(): void
    {
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->create($this->makeInput());

        self::assertInstanceOf(Document::class, $captured);
        self::assertNotNull($captured->getReference());
    }

    public function testCreateAppliesTitleAndStatus(): void
    {
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->create($this->makeInput('Annual Report', 'Year 2025', DocumentStatusEnum::Published));

        self::assertSame('Annual Report', $captured->getTitle());
        self::assertSame('Year 2025', $captured->getDescription());
        self::assertSame(DocumentStatusEnum::Published, $captured->getStatus());
    }

    public function testCreateCallsPersistAndFlush(): void
    {
        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->create($this->makeInput());
    }

    public function testCreateReturnsDocumentInstance(): void
    {
        $result = $this->manager->create($this->makeInput('Contract'));

        self::assertInstanceOf(DocumentInterface::class, $result);
        self::assertSame('Contract', $result->getTitle());
    }

    public function testCreateRecordsVersionWhenFileIsAttached(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $this->mediaRepository->method('find')->willReturn($file);

        $capturedVersion = null;
        $this->captureDocumentVersion($capturedVersion);

        $this->manager->create($this->makeInput(fileId: 5));

        self::assertInstanceOf(DocumentVersion::class, $capturedVersion);
    }

    public function testCreateDoesNotRecordVersionWithoutFile(): void
    {
        $capturedVersion = null;
        $this->captureDocumentVersion($capturedVersion);

        $this->manager->create($this->makeInput(fileId: null));

        self::assertNull($capturedVersion);
    }

    public function testCreateResolvesCategory(): void
    {
        $category = $this->createStub(DocumentCategoryInterface::class);
        $this->categoryRepository->method('find')->willReturn($category);

        $captured = null;
        $this->captureDocument($captured);

        $this->manager->create($this->makeInput(categoryId: 3));

        self::assertSame($category, $captured->getCategory());
    }

    public function testCreateWithNullCategoryIdSetsNullCategory(): void
    {
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->create($this->makeInput(categoryId: null));

        self::assertNull($captured->getCategory());
    }

    public function testCreateResolvesAndAttachesTags(): void
    {
        $tag1 = $this->createStub(DocumentTagInterface::class);
        $tag2 = $this->createStub(DocumentTagInterface::class);

        $this->tagRepository->method('find')->willReturnCallback(
            static fn (int $id): ?DocumentTagInterface => match ($id) {
                1 => $tag1,
                2 => $tag2,
                default => null,
            }
        );

        $captured = null;
        $this->captureDocument($captured);

        $this->manager->create($this->makeInput(tagIds: [1, 2]));

        self::assertCount(2, $captured->getTags());
    }

    public function testCreateResolvesFolder(): void
    {
        $folder = $this->createStub(DocumentFolderInterface::class);
        $this->folderRepository->method('find')->willReturn($folder);

        $captured = null;
        $this->captureDocument($captured);

        $this->manager->create($this->makeInput(folderId: 7));

        self::assertSame($folder, $captured->getFolder());
    }

    // --- update() ---

    public function testUpdateAppliesNewTitle(): void
    {
        $document = new Document();
        $document->setTitle('Old')->setStatus(DocumentStatusEnum::Draft);

        $this->manager->update($document, $this->makeInput('New', status: DocumentStatusEnum::Published));

        self::assertSame('New', $document->getTitle());
        self::assertSame(DocumentStatusEnum::Published, $document->getStatus());
    }

    public function testUpdateCallsFlush(): void
    {
        $document = new Document();
        $document->setTitle('X')->setStatus(DocumentStatusEnum::Draft);

        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($document, $this->makeInput());
    }

    public function testUpdateRecordsVersionWhenFileChanges(): void
    {
        $oldFile = $this->createStub(MediaInterface::class);
        $oldFile->method('getId')->willReturn(1);

        $newFile = $this->createStub(MediaInterface::class);
        $newFile->method('getId')->willReturn(2);

        $document = new Document();
        $document->setTitle('Doc')->setStatus(DocumentStatusEnum::Draft)->setFile($oldFile);

        $this->mediaRepository->method('find')->willReturn($newFile);

        $capturedVersion = null;
        $this->captureDocumentVersion($capturedVersion);

        $this->manager->update($document, $this->makeInput(fileId: 2));

        self::assertInstanceOf(DocumentVersion::class, $capturedVersion);
    }

    public function testUpdateDoesNotRecordVersionWhenFileUnchanged(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $file->method('getId')->willReturn(10);

        $document = new Document();
        $document->setTitle('Doc')->setStatus(DocumentStatusEnum::Draft)->setFile($file);

        $this->mediaRepository->method('find')->willReturn($file);

        $capturedVersion = null;
        $this->captureDocumentVersion($capturedVersion);

        $this->manager->update($document, $this->makeInput(fileId: 10));

        self::assertNull($capturedVersion);
    }

    public function testUpdateDoesNotRecordVersionWhenNewFileIdIsNull(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $file->method('getId')->willReturn(5);

        $document = new Document();
        $document->setTitle('Doc')->setStatus(DocumentStatusEnum::Draft)->setFile($file);

        $capturedVersion = null;
        $this->captureDocumentVersion($capturedVersion);

        $this->manager->update($document, $this->makeInput(fileId: null));

        self::assertNull($capturedVersion);
    }

    public function testUpdateClearsPreviousTagsAndAppliesNew(): void
    {
        $oldTag = $this->createStub(DocumentTagInterface::class);
        $newTag = $this->createStub(DocumentTagInterface::class);

        $this->tagRepository->method('find')->willReturn($newTag);

        $document = new Document();
        $document->setTitle('Doc')->setStatus(DocumentStatusEnum::Draft)->addTag($oldTag);

        $this->manager->update($document, $this->makeInput(tagIds: [99]));

        self::assertCount(1, $document->getTags());
        self::assertTrue($document->getTags()->contains($newTag));
    }

    // --- delete() ---

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $document = new Document();
        $document->setTitle('Doc')->setStatus(DocumentStatusEnum::Draft);

        $this->entityManager->expects(self::once())->method('remove')->with($document);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($document);
    }
}
