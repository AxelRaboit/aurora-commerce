<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mood_message column to users (short tagline shown on profile)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD mood_message VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP mood_message');
    }
}
