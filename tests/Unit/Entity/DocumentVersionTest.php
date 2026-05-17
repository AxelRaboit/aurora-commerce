<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Entity\DocumentVersion;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DocumentVersionTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $before = new DateTimeImmutable();
        $version = new DocumentVersion();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $version->getCreatedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $version->getCreatedAt()->getTimestamp());
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new DocumentVersion())->getId());
    }

    public function testNoteIsNullByDefault(): void
    {
        self::assertNull((new DocumentVersion())->getNote());
    }

    public function testDocumentGetterAndSetter(): void
    {
        $document = $this->createStub(DocumentInterface::class);
        $version = (new DocumentVersion())->setDocument($document);

        self::assertSame($document, $version->getDocument());
    }

    public function testFileGetterAndSetter(): void
    {
        $file = $this->createStub(MediaInterface::class);
        $version = (new DocumentVersion())->setFile($file);

        self::assertSame($file, $version->getFile());
    }

    public function testVersionNumberGetterAndSetter(): void
    {
        $version = (new DocumentVersion())->setVersionNumber(3);

        self::assertSame(3, $version->getVersionNumber());
    }

    public function testNoteGetterAndSetter(): void
    {
        $version = (new DocumentVersion())->setNote('Initial upload.');

        self::assertSame('Initial upload.', $version->getNote());

        $version->setNote(null);
        self::assertNull($version->getNote());
    }

    public function testSettersReturnSelf(): void
    {
        $version = new DocumentVersion();

        self::assertSame($version, $version->setDocument($this->createStub(DocumentInterface::class)));
        self::assertSame($version, $version->setFile($this->createStub(MediaInterface::class)));
        self::assertSame($version, $version->setVersionNumber(1));
        self::assertSame($version, $version->setNote('n'));
    }
}
