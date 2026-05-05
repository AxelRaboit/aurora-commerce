<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505202524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create GED module tables (ged_document_categories, ged_documents)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_ged_category_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_ged_document_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE ged_document_categories (id INT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(180) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9327DC57989D9B62 ON ged_document_categories (slug)');
        $this->addSql('CREATE TABLE ged_documents (id INT NOT NULL, category_id INT DEFAULT NULL, file_id INT DEFAULT NULL, reference VARCHAR(32) DEFAULT NULL, title VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_20AEDFC5AEA34913 ON ged_documents (reference)');
        $this->addSql('CREATE INDEX IDX_20AEDFC512469DE2 ON ged_documents (category_id)');
        $this->addSql('CREATE INDEX IDX_20AEDFC593CB796C ON ged_documents (file_id)');
        $this->addSql('ALTER TABLE ged_documents ADD CONSTRAINT FK_20AEDFC512469DE2 FOREIGN KEY (category_id) REFERENCES ged_document_categories (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE ged_documents ADD CONSTRAINT FK_20AEDFC593CB796C FOREIGN KEY (file_id) REFERENCES media (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ged_documents DROP CONSTRAINT FK_20AEDFC512469DE2');
        $this->addSql('ALTER TABLE ged_documents DROP CONSTRAINT FK_20AEDFC593CB796C');
        $this->addSql('DROP TABLE ged_document_categories');
        $this->addSql('DROP TABLE ged_documents');
        $this->addSql('DROP SEQUENCE seq_ged_category_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_ged_document_id CASCADE');
    }
}
