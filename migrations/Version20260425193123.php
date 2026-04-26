<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425193123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add comments_enabled column on posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts ADD comments_enabled BOOLEAN DEFAULT true NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts DROP comments_enabled');
    }
}
