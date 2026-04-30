<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Photo galleries: per-invitee magic-link invitations.
 */
final class Version20260430150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Photo: photo_gallery_invites table for per-invitee magic-link access.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE photo_gallery_invites (
            id SERIAL NOT NULL,
            gallery_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            email VARCHAR(180) NOT NULL,
            token VARCHAR(64) NOT NULL,
            visitor_token VARCHAR(64) NOT NULL,
            invited_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            last_seen_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_invite_token ON photo_gallery_invites (token)');
        $this->addSql('CREATE INDEX idx_invite_visitor_token ON photo_gallery_invites (visitor_token)');
        $this->addSql('CREATE UNIQUE INDEX uniq_invite_per_email ON photo_gallery_invites (gallery_id, email)');
        $this->addSql('ALTER TABLE photo_gallery_invites ADD CONSTRAINT FK_pgi_gallery FOREIGN KEY (gallery_id) REFERENCES photo_galleries (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE photo_gallery_invites');
    }
}
