<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503220251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename sku → reference on erp_products, sku_snapshot → reference_snapshot on ecommerce_order_lines';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ecommerce_order_lines RENAME COLUMN sku_snapshot TO reference_snapshot');
        $this->addSql('DROP INDEX uniq_erp_product_sku');
        $this->addSql('ALTER TABLE erp_products RENAME COLUMN sku TO reference');
        $this->addSql('CREATE UNIQUE INDEX uniq_erp_product_reference ON erp_products (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ecommerce_order_lines RENAME COLUMN reference_snapshot TO sku_snapshot');
        $this->addSql('DROP INDEX uniq_erp_product_reference');
        $this->addSql('ALTER TABLE erp_products RENAME COLUMN reference TO sku');
        $this->addSql('CREATE UNIQUE INDEX uniq_erp_product_sku ON erp_products (sku)');
    }
}
