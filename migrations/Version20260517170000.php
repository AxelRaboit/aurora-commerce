<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the awaiting_confirmation flag on core_assistant_messages so the
 * chat loop can pause on assistant messages whose tool calls require
 * explicit user approval (filesystem_write, future shell exec, …).
 */
final class Version20260517170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add awaiting_confirmation column on core_assistant_messages.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_assistant_messages ADD awaiting_confirmation BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_assistant_messages DROP awaiting_confirmation');
    }
}
