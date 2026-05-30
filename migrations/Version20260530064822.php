<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * GED parity-with-Media foundation: focal point + responsive variants on
 * Document so it can render images on the frontend without the Media library
 * (Phase 1 of the docs/aurora-core/todo/media-ged-merge.md plan).
 */
final class Version20260530064822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'GED parity: add focal_x, focal_y, variants to core_ged_documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_ged_documents ADD focal_x DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE core_ged_documents ADD focal_y DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE core_ged_documents ADD variants JSON DEFAULT \'{}\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_ged_documents DROP focal_x');
        $this->addSql('ALTER TABLE core_ged_documents DROP focal_y');
        $this->addSql('ALTER TABLE core_ged_documents DROP variants');
    }
}
