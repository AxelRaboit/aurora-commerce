<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523175759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Welding module — template layer: WorkflowTemplate + WorkflowStepTemplate + WorkflowStepPdfTemplate, with FK chain step → step-pdf → pdfform_templates and self-FK for parent version tracking.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_welding_workflow_step_pdf_template_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_welding_workflow_step_template_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_welding_workflow_template_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_welding_workflow_step_pdf_templates (position INT DEFAULT 0 NOT NULL, required BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, workflow_step_template_id INT NOT NULL, pdf_template_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EB8119086BE5D675 ON core_welding_workflow_step_pdf_templates (workflow_step_template_id)');
        $this->addSql('CREATE INDEX IDX_EB811908CA5AA7D3 ON core_welding_workflow_step_pdf_templates (pdf_template_id)');
        $this->addSql('CREATE TABLE core_welding_workflow_step_templates (position INT DEFAULT 0 NOT NULL, title VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, requires_validation BOOLEAN DEFAULT false NOT NULL, validator_role VARCHAR(30) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, workflow_template_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4E63003D53F4E445 ON core_welding_workflow_step_templates (workflow_template_id)');
        $this->addSql('CREATE TABLE core_welding_workflow_templates (title VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, applicable_to VARCHAR(100) DEFAULT NULL, version INT DEFAULT 1 NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id INT NOT NULL, parent_version_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_24C6AA70CFFA355 ON core_welding_workflow_templates (parent_version_id)');
        $this->addSql('ALTER TABLE core_welding_workflow_step_pdf_templates ADD CONSTRAINT FK_EB8119086BE5D675 FOREIGN KEY (workflow_step_template_id) REFERENCES core_welding_workflow_step_templates (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflow_step_pdf_templates ADD CONSTRAINT FK_EB811908CA5AA7D3 FOREIGN KEY (pdf_template_id) REFERENCES core_pdfform_templates (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflow_step_templates ADD CONSTRAINT FK_4E63003D53F4E445 FOREIGN KEY (workflow_template_id) REFERENCES core_welding_workflow_templates (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_welding_workflow_templates ADD CONSTRAINT FK_24C6AA70CFFA355 FOREIGN KEY (parent_version_id) REFERENCES core_welding_workflow_templates (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_welding_workflow_step_pdf_templates DROP CONSTRAINT FK_EB8119086BE5D675');
        $this->addSql('ALTER TABLE core_welding_workflow_step_pdf_templates DROP CONSTRAINT FK_EB811908CA5AA7D3');
        $this->addSql('ALTER TABLE core_welding_workflow_step_templates DROP CONSTRAINT FK_4E63003D53F4E445');
        $this->addSql('ALTER TABLE core_welding_workflow_templates DROP CONSTRAINT FK_24C6AA70CFFA355');
        $this->addSql('DROP TABLE core_welding_workflow_step_pdf_templates');
        $this->addSql('DROP TABLE core_welding_workflow_step_templates');
        $this->addSql('DROP TABLE core_welding_workflow_templates');
        $this->addSql('DROP SEQUENCE seq_core_welding_workflow_step_pdf_template_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_welding_workflow_step_template_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_welding_workflow_template_id CASCADE');
    }
}
