<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426190557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ecommerce_orders ALTER address_line1 DROP NOT NULL');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER city DROP NOT NULL');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER postal_code DROP NOT NULL');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER country DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ecommerce_orders ALTER address_line1 SET NOT NULL');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER city SET NOT NULL');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER postal_code SET NOT NULL');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER country SET NOT NULL');
    }
}
