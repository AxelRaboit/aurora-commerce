<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509165919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'feat: VaultFolder — add parent_id for infinite folder nesting';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_vault_folders ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_vault_folders ADD CONSTRAINT FK_288B4A6E727ACA70 FOREIGN KEY (parent_id) REFERENCES core_vault_folders (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_288B4A6E727ACA70 ON core_vault_folders (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_vault_folders DROP CONSTRAINT FK_288B4A6E727ACA70');
        $this->addSql('DROP INDEX IDX_288B4A6E727ACA70');
        $this->addSql('ALTER TABLE core_vault_folders DROP parent_id');
    }
}
