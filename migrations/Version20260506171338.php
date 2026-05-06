<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506171338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename user type values: admin→backend, frontend_user→frontend';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE users SET type = 'backend' WHERE type = 'admin'");
        $this->addSql("UPDATE users SET type = 'frontend' WHERE type = 'frontend_user'");
        $this->addSql("ALTER TABLE users ALTER type SET DEFAULT 'backend'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE users SET type = 'admin' WHERE type = 'backend'");
        $this->addSql("UPDATE users SET type = 'frontend_user' WHERE type = 'frontend'");
        $this->addSql("ALTER TABLE users ALTER type SET DEFAULT 'admin'");
    }
}
