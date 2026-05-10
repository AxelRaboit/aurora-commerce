<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add requires_signature column to core_pdfform_templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_pdfform_templates ADD requires_signature BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_pdfform_templates DROP COLUMN requires_signature');
    }
}
