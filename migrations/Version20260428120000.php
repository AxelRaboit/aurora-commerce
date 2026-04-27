<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manager_id self-reference to users (manager → subordinates)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD manager_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_1483A5E9783E3463 ON users (manager_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9783E3463');
        $this->addSql('DROP INDEX IDX_1483A5E9783E3463');
        $this->addSql('ALTER TABLE users DROP manager_id');
    }
}
