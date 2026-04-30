<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Photo galleries: per-visitor finalization (multi-validation).
 */
final class Version20260430120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Photo: photo_gallery_finalizations table for per-visitor selection validation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE photo_gallery_finalizations (
            id SERIAL NOT NULL,
            gallery_id INT NOT NULL,
            visitor_token VARCHAR(64) NOT NULL,
            visitor_name VARCHAR(200) DEFAULT NULL,
            visitor_email VARCHAR(180) DEFAULT NULL,
            finalized_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_finalization_per_visitor ON photo_gallery_finalizations (gallery_id, visitor_token)');
        $this->addSql('CREATE INDEX idx_finalization_token ON photo_gallery_finalizations (visitor_token)');
        $this->addSql('ALTER TABLE photo_gallery_finalizations ADD CONSTRAINT FK_pgf_gallery FOREIGN KEY (gallery_id) REFERENCES photo_galleries (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE photo_gallery_finalizations');
    }
}
