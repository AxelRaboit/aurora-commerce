<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425134312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename registration_enabled setting key to admin_registration_enabled';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE settings SET setting_key = 'admin_registration_enabled' WHERE setting_key = 'registration_enabled'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE settings SET setting_key = 'registration_enabled' WHERE setting_key = 'admin_registration_enabled'");
    }
}
