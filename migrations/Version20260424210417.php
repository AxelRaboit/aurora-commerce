<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424210417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add variants JSON column on media for generated image variants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE media ADD variants JSON DEFAULT '{}' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media DROP variants');
    }
}
