<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510134239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE seq_core_pdfform_document_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_pdfform_template_field_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_pdfform_template_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_pdfform_documents (reference VARCHAR(32) DEFAULT NULL, status VARCHAR(20) NOT NULL, label VARCHAR(300) DEFAULT NULL, field_values JSON NOT NULL, context_type VARCHAR(100) DEFAULT NULL, context_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, template_id INT DEFAULT NULL, file_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_25ECD2A0AEA34913 ON core_pdfform_documents (reference)');
        $this->addSql('CREATE INDEX IDX_25ECD2A05DA0FB8 ON core_pdfform_documents (template_id)');
        $this->addSql('CREATE INDEX IDX_25ECD2A093CB796C ON core_pdfform_documents (file_id)');
        $this->addSql('CREATE TABLE core_pdfform_template_fields (pdf_field_name VARCHAR(200) NOT NULL, label VARCHAR(200) NOT NULL, field_type VARCHAR(20) NOT NULL, mapping_key VARCHAR(255) DEFAULT NULL, default_value VARCHAR(500) DEFAULT NULL, position INT NOT NULL, id INT NOT NULL, template_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_8B744FA85DA0FB8 ON core_pdfform_template_fields (template_id)');
        $this->addSql('CREATE TABLE core_pdfform_templates (name VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, file_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E874DDA693CB796C ON core_pdfform_templates (file_id)');
        $this->addSql('ALTER TABLE core_pdfform_documents ADD CONSTRAINT FK_25ECD2A05DA0FB8 FOREIGN KEY (template_id) REFERENCES core_pdfform_templates (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_pdfform_documents ADD CONSTRAINT FK_25ECD2A093CB796C FOREIGN KEY (file_id) REFERENCES core_media (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_pdfform_template_fields ADD CONSTRAINT FK_8B744FA85DA0FB8 FOREIGN KEY (template_id) REFERENCES core_pdfform_templates (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_pdfform_templates ADD CONSTRAINT FK_E874DDA693CB796C FOREIGN KEY (file_id) REFERENCES core_media (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_core_pdfform_document_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_pdfform_template_field_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_pdfform_template_id CASCADE');
        $this->addSql('ALTER TABLE core_pdfform_documents DROP CONSTRAINT FK_25ECD2A05DA0FB8');
        $this->addSql('ALTER TABLE core_pdfform_documents DROP CONSTRAINT FK_25ECD2A093CB796C');
        $this->addSql('ALTER TABLE core_pdfform_template_fields DROP CONSTRAINT FK_8B744FA85DA0FB8');
        $this->addSql('ALTER TABLE core_pdfform_templates DROP CONSTRAINT FK_E874DDA693CB796C');
        $this->addSql('DROP TABLE core_pdfform_documents');
        $this->addSql('DROP TABLE core_pdfform_template_fields');
        $this->addSql('DROP TABLE core_pdfform_templates');
    }
}
