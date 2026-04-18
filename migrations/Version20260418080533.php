<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418080533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE users ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE users ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER locale DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP created_at');
        $this->addSql('ALTER TABLE users DROP updated_at');
        $this->addSql('ALTER TABLE users ALTER locale SET DEFAULT \'fr\'');
    }
}
