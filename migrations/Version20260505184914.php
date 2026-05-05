<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505184914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add privileges JSON column to users table for fine-grained access control';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD privileges JSON NOT NULL DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE users ALTER privileges DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP privileges');
    }
}
