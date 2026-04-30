<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Photo galleries: store EXIF DateTimeOriginal per item for burst grouping.
 */
final class Version20260430140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Photo: add taken_at column on photo_gallery_items (EXIF DateTimeOriginal).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photo_gallery_items ADD taken_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photo_gallery_items DROP taken_at');
    }
}
