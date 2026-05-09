<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509154812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'feat: Vault module — core_vault_entries, core_vault_folders, core_vault_user_configs tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_vault_entry_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_vault_folder_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_vault_user_config_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_vault_entries (type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, encrypted_data TEXT NOT NULL, iv VARCHAR(64) NOT NULL, is_favorite BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, folder_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_FB442AA4A76ED395 ON core_vault_entries (user_id)');
        $this->addSql('CREATE INDEX IDX_FB442AA4162CB942 ON core_vault_entries (folder_id)');
        $this->addSql('CREATE TABLE core_vault_folders (name VARCHAR(100) NOT NULL, color VARCHAR(7) DEFAULT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_288B4A6EA76ED395 ON core_vault_folders (user_id)');
        $this->addSql('CREATE TABLE core_vault_user_configs (argon2_salt VARCHAR(128) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6558F307A76ED395 ON core_vault_user_configs (user_id)');
        $this->addSql('ALTER TABLE core_vault_entries ADD CONSTRAINT FK_FB442AA4A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_vault_entries ADD CONSTRAINT FK_FB442AA4162CB942 FOREIGN KEY (folder_id) REFERENCES core_vault_folders (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_vault_folders ADD CONSTRAINT FK_288B4A6EA76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_vault_user_configs ADD CONSTRAINT FK_6558F307A76ED395 FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_vault_entry_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_vault_folder_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_vault_user_config_id CASCADE');
        $this->addSql('ALTER TABLE core_vault_entries DROP CONSTRAINT FK_FB442AA4A76ED395');
        $this->addSql('ALTER TABLE core_vault_entries DROP CONSTRAINT FK_FB442AA4162CB942');
        $this->addSql('ALTER TABLE core_vault_folders DROP CONSTRAINT FK_288B4A6EA76ED395');
        $this->addSql('ALTER TABLE core_vault_user_configs DROP CONSTRAINT FK_6558F307A76ED395');
        $this->addSql('DROP TABLE core_vault_entries');
        $this->addSql('DROP TABLE core_vault_folders');
        $this->addSql('DROP TABLE core_vault_user_configs');
    }
}
