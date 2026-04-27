<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427223317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise media audit log actions: strip "media." prefix (e.g. "media.updated" → "updated")';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE audit_logs SET action = REGEXP_REPLACE(action, '^media\\.', '') WHERE module = 'media' AND action LIKE 'media.%'"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE audit_logs SET action = 'media.' || action WHERE module = 'media' AND action NOT LIKE 'media.%'"
        );
    }
}
