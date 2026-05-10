<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510155826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase app_sequence_counters.prefix from VARCHAR(30) to VARCHAR(64)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_sequence_counters ALTER COLUMN prefix TYPE VARCHAR(64)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_sequence_counters ALTER COLUMN prefix TYPE VARCHAR(30)');
    }
}
