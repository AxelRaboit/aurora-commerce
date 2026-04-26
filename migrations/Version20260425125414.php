<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425125414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user type (admin/front_user), replace unique email with unique (email, type)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_1483a5e9e7927c74');
        $this->addSql('ALTER TABLE users ADD type VARCHAR(20) DEFAULT \'admin\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email_type ON users (email, type)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_user_email_type');
        $this->addSql('ALTER TABLE users DROP type');
        $this->addSql('CREATE UNIQUE INDEX uniq_1483a5e9e7927c74 ON users (email)');
    }
}
