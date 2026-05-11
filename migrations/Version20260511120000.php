<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add disabled_modules column to core_users (per-user module access overrides)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE core_users ADD disabled_modules JSON DEFAULT '[]' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_users DROP COLUMN disabled_modules');
    }
}
