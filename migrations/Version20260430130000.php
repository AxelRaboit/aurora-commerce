<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Photo galleries: stable per-gallery photo number for unambiguous reference.
 */
final class Version20260430130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Photo: add stable number column on photo_gallery_items (assigned at creation, never reordered).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photo_gallery_items ADD number INT DEFAULT 0 NOT NULL');

        // Backfill existing rows: number = (position + 1) per gallery, so admins
        // see contiguous, intuitive numbers on legacy galleries that were
        // implicitly ordered by position.
        $this->addSql('UPDATE photo_gallery_items SET number = position + 1');

        $this->addSql('CREATE UNIQUE INDEX uniq_gallery_number ON photo_gallery_items (gallery_id, number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_gallery_number');
        $this->addSql('ALTER TABLE photo_gallery_items DROP number');
    }
}
