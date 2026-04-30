<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Photo galleries: pick kinds, max picks, visitor comments.
 */
final class Version20260429232640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Photo: maxPicks + allowVisitorComments on galleries, kind on picks, gallery_item_comments table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photo_galleries ADD max_picks INT DEFAULT NULL');
        $this->addSql('ALTER TABLE photo_galleries ADD allow_visitor_comments BOOLEAN DEFAULT FALSE NOT NULL');

        $this->addSql("ALTER TABLE photo_gallery_picks ADD kind VARCHAR(32) DEFAULT 'favorite' NOT NULL");
        $this->addSql('DROP INDEX uniq_pick_per_visitor');
        $this->addSql('CREATE UNIQUE INDEX uniq_pick_per_visitor ON photo_gallery_picks (gallery_item_id, visitor_token, kind)');

        $this->addSql('CREATE TABLE photo_gallery_item_comments (
            id SERIAL NOT NULL,
            gallery_item_id INT NOT NULL,
            visitor_token VARCHAR(64) NOT NULL,
            visitor_name VARCHAR(200) DEFAULT NULL,
            visitor_email VARCHAR(180) DEFAULT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_comment_item ON photo_gallery_item_comments (gallery_item_id)');
        $this->addSql('CREATE INDEX idx_comment_token ON photo_gallery_item_comments (visitor_token)');
        $this->addSql('ALTER TABLE photo_gallery_item_comments ADD CONSTRAINT FK_pgic_item FOREIGN KEY (gallery_item_id) REFERENCES photo_gallery_items (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE photo_gallery_item_comments');

        $this->addSql('DROP INDEX uniq_pick_per_visitor');
        $this->addSql('CREATE UNIQUE INDEX uniq_pick_per_visitor ON photo_gallery_picks (gallery_item_id, visitor_token)');
        $this->addSql('ALTER TABLE photo_gallery_picks DROP kind');

        $this->addSql('ALTER TABLE photo_galleries DROP max_picks');
        $this->addSql('ALTER TABLE photo_galleries DROP allow_visitor_comments');
    }
}
