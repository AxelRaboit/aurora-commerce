<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424200249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add post_related_posts join table for directional related-posts relation';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE post_related_posts (post_id INT NOT NULL, related_post_id INT NOT NULL, PRIMARY KEY (post_id, related_post_id))');
        $this->addSql('CREATE INDEX IDX_E7ADE2C24B89032C ON post_related_posts (post_id)');
        $this->addSql('CREATE INDEX IDX_E7ADE2C27490C989 ON post_related_posts (related_post_id)');
        $this->addSql('ALTER TABLE post_related_posts ADD CONSTRAINT FK_E7ADE2C24B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE post_related_posts ADD CONSTRAINT FK_E7ADE2C27490C989 FOREIGN KEY (related_post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_related_posts DROP CONSTRAINT FK_E7ADE2C24B89032C');
        $this->addSql('ALTER TABLE post_related_posts DROP CONSTRAINT FK_E7ADE2C27490C989');
        $this->addSql('DROP TABLE post_related_posts');
    }
}
