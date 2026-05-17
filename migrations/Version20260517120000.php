<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema for the Assistant module: per-user chat conversations with
 * the local Ollama LLM. Titles and message content are stored via the
 * encrypted_text Doctrine type — payloads can contain sensitive paths/IDs.
 */
final class Version20260517120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core_assistant_conversations and core_assistant_messages tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_core_assistant_conversation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_core_assistant_message_id INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE core_assistant_conversations (id INT NOT NULL, user_id INT NOT NULL, agency_id INT DEFAULT NULL, title TEXT DEFAULT NULL, model VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_assistant_conversations_user ON core_assistant_conversations (user_id)');
        $this->addSql('CREATE INDEX IDX_ASSISTCONV_AGENCY ON core_assistant_conversations (agency_id)');
        $this->addSql('ALTER TABLE core_assistant_conversations ADD CONSTRAINT FK_ASSISTCONV_USER FOREIGN KEY (user_id) REFERENCES core_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_assistant_conversations ADD CONSTRAINT FK_ASSISTCONV_AGENCY FOREIGN KEY (agency_id) REFERENCES core_agencies (id) ON DELETE SET NULL NOT DEFERRABLE');

        $this->addSql('CREATE TABLE core_assistant_messages (id INT NOT NULL, conversation_id INT NOT NULL, role VARCHAR(20) NOT NULL, content TEXT NOT NULL, tool_calls JSON DEFAULT NULL, tool_call_id VARCHAR(100) DEFAULT NULL, tool_name VARCHAR(100) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_assistant_messages_conversation ON core_assistant_messages (conversation_id)');
        $this->addSql('ALTER TABLE core_assistant_messages ADD CONSTRAINT FK_ASSISTMSG_CONV FOREIGN KEY (conversation_id) REFERENCES core_assistant_conversations (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_assistant_messages DROP CONSTRAINT FK_ASSISTMSG_CONV');
        $this->addSql('ALTER TABLE core_assistant_conversations DROP CONSTRAINT FK_ASSISTCONV_USER');
        $this->addSql('ALTER TABLE core_assistant_conversations DROP CONSTRAINT FK_ASSISTCONV_AGENCY');
        $this->addSql('DROP TABLE core_assistant_messages');
        $this->addSql('DROP TABLE core_assistant_conversations');
        $this->addSql('DROP SEQUENCE seq_core_assistant_message_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_core_assistant_conversation_id CASCADE');
    }
}
