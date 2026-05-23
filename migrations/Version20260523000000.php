<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add attachment_original_name column to core_personal_finance_transaction (preserves user-facing filename for receipt downloads).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_transaction ADD attachment_original_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_personal_finance_transaction DROP attachment_original_name');
    }
}
