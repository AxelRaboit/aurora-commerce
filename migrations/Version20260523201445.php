<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename PdfForm tables + sequences to live under the Welding namespace.
 *
 * Part of the PdfForm → Welding module merge. Doctrine's auto-diff would
 * DROP+CREATE (destroying data), so this migration uses ALTER ... RENAME
 * everywhere — tables, sequences, indices, constraints — to keep existing
 * rows intact. PG follows table by OID so the FK from
 * core_welding_workflow_step_pdf_templates.pdf_template_id still resolves
 * to the renamed core_welding_pdf_templates without manual fixup, but we
 * rename the FK constraint name itself for consistency.
 */
final class Version20260523201445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename core_pdfform_* tables/sequences/indices to core_welding_pdf_* (PdfForm → Welding merge).';
    }

    public function up(Schema $schema): void
    {
        // Tables
        $this->addSql('ALTER TABLE core_pdfform_templates RENAME TO core_welding_pdf_templates');
        $this->addSql('ALTER TABLE core_pdfform_template_fields RENAME TO core_welding_pdf_template_fields');
        $this->addSql('ALTER TABLE core_pdfform_documents RENAME TO core_welding_pdf_documents');

        // Sequences
        $this->addSql('ALTER SEQUENCE seq_core_pdfform_template_id RENAME TO seq_core_welding_pdf_template_id');
        $this->addSql('ALTER SEQUENCE seq_core_pdfform_template_field_id RENAME TO seq_core_welding_pdf_template_field_id');
        $this->addSql('ALTER SEQUENCE seq_core_pdfform_document_id RENAME TO seq_core_welding_pdf_document_id');

        // Indices — Doctrine generates name hashes based on table name, so the
        // mapping expects names like IDX_CBD8B12493CB796C on the new table; the
        // old auto-generated names (idx_e874dda693cb796c) need to be renamed to
        // match the metadata-derived expectations.
        $this->addSql('ALTER INDEX idx_e874dda693cb796c RENAME TO IDX_CBD8B12493CB796C');
        $this->addSql('ALTER INDEX idx_8b744fa85da0fb8 RENAME TO IDX_B9783E335DA0FB8');
        $this->addSql('ALTER INDEX idx_25ecd2a05da0fb8 RENAME TO IDX_640BE225DA0FB8');
        $this->addSql('ALTER INDEX uniq_25ecd2a0aea34913 RENAME TO UNIQ_640BE22AEA34913');

        // Foreign-key constraints — rename to match Doctrine-derived names so
        // future schema:validate doesn't flag drift.
        $this->addSql('ALTER TABLE core_welding_pdf_templates RENAME CONSTRAINT fk_e874dda693cb796c TO FK_CBD8B12493CB796C');
        $this->addSql('ALTER TABLE core_welding_pdf_template_fields RENAME CONSTRAINT fk_8b744fa85da0fb8 TO FK_B9783E335DA0FB8');
        $this->addSql('ALTER TABLE core_welding_pdf_documents RENAME CONSTRAINT fk_25ecd2a05da0fb8 TO FK_640BE225DA0FB8');

        // FK on the workflow-step-pdf-template join table follows the renamed
        // target by OID, no constraint rename needed (the name was already
        // properly cased from sprint 2's migration).
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_welding_pdf_documents RENAME CONSTRAINT FK_640BE225DA0FB8 TO fk_25ecd2a05da0fb8');
        $this->addSql('ALTER TABLE core_welding_pdf_template_fields RENAME CONSTRAINT FK_B9783E335DA0FB8 TO fk_8b744fa85da0fb8');
        $this->addSql('ALTER TABLE core_welding_pdf_templates RENAME CONSTRAINT FK_CBD8B12493CB796C TO fk_e874dda693cb796c');

        $this->addSql('ALTER INDEX UNIQ_640BE22AEA34913 RENAME TO uniq_25ecd2a0aea34913');
        $this->addSql('ALTER INDEX IDX_640BE225DA0FB8 RENAME TO idx_25ecd2a05da0fb8');
        $this->addSql('ALTER INDEX IDX_B9783E335DA0FB8 RENAME TO idx_8b744fa85da0fb8');
        $this->addSql('ALTER INDEX IDX_CBD8B12493CB796C RENAME TO idx_e874dda693cb796c');

        $this->addSql('ALTER SEQUENCE seq_core_welding_pdf_document_id RENAME TO seq_core_pdfform_document_id');
        $this->addSql('ALTER SEQUENCE seq_core_welding_pdf_template_field_id RENAME TO seq_core_pdfform_template_field_id');
        $this->addSql('ALTER SEQUENCE seq_core_welding_pdf_template_id RENAME TO seq_core_pdfform_template_id');

        $this->addSql('ALTER TABLE core_welding_pdf_documents RENAME TO core_pdfform_documents');
        $this->addSql('ALTER TABLE core_welding_pdf_template_fields RENAME TO core_pdfform_template_fields');
        $this->addSql('ALTER TABLE core_welding_pdf_templates RENAME TO core_pdfform_templates');
    }
}
