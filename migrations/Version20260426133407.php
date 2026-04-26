<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426133407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_name snapshot column to audit_logs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs ADD user_name VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs DROP user_name');
    }
}
