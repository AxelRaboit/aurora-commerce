<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514100701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_media ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_media ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_media_folders ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_media_folders ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_post_revisions ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_post_revisions ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_post_slug_history ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_post_slug_history ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_posts ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_posts ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomies ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomies ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomy_terms ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomy_terms ALTER updated_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_media ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_media ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_media_folders ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_media_folders ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_post_revisions ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_post_revisions ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_post_slug_history ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_post_slug_history ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_posts ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_posts ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomies ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomies ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomy_terms ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE core_taxonomy_terms ALTER updated_at DROP NOT NULL');
    }
}
