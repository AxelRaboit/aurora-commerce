<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515091752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add hidden_nav_sections + hidden_nav_items JSON columns on core_users (user-managed sidemenu preferences).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_users ADD hidden_nav_sections JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE core_users ADD hidden_nav_items JSON DEFAULT \'[]\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_users DROP hidden_nav_sections');
        $this->addSql('ALTER TABLE core_users DROP hidden_nav_items');
    }
}
