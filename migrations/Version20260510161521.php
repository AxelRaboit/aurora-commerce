<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510161521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy project sequences no longer managed by Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS seq_prj CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS seq_prjc CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS seq_tsk CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS seq_prj INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS seq_prjc INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS seq_tsk INCREMENT BY 1 MINVALUE 1 START 1');
    }
}
