<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426232528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove search_vector column, normalize index names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN users.email_verification_expires_at IS \'\'');
        $this->addSql('DROP INDEX IF EXISTS idx_post_translations_search_vector');
        $this->addSql('ALTER TABLE post_translations DROP COLUMN IF EXISTS search_vector');
        $this->addSql('ALTER INDEX IF EXISTS idx_6e5706165ff69b7d RENAME TO IDX_7C0B37265FF69B7D');
        $this->addSql('ALTER INDEX IF EXISTS idx_fft_field RENAME TO IDX_F759C338443707B0');
        $this->addSql('ALTER INDEX IF EXISTS uniq_field_translation_locale RENAME TO UNIQ_F759C338443707B04180C698');
        $this->addSql('ALTER INDEX IF EXISTS idx_form_trans_form RENAME TO IDX_ACF091E55FF69B7D');
        $this->addSql('ALTER INDEX IF EXISTS uniq_form_translation_locale RENAME TO UNIQ_ACF091E55FF69B7D4180C698');
        $this->addSql('ALTER INDEX IF EXISTS uniq_form_translation_slug RENAME TO UNIQ_ACF091E54180C698989D9B62');
        $this->addSql('COMMENT ON COLUMN forms.created_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN forms.updated_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN form_submissions.submitted_at IS \'\'');
        $this->addSql('ALTER INDEX IF EXISTS idx_8540af9f5ff69b7d RENAME TO IDX_C80AF9E65FF69B7D');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX IF EXISTS IDX_7C0B37265FF69B7D RENAME TO idx_6e5706165ff69b7d');
        $this->addSql('ALTER INDEX IF EXISTS IDX_F759C338443707B0 RENAME TO idx_fft_field');
        $this->addSql('ALTER INDEX IF EXISTS UNIQ_F759C338443707B04180C698 RENAME TO uniq_field_translation_locale');
        $this->addSql('ALTER INDEX IF EXISTS IDX_ACF091E55FF69B7D RENAME TO idx_form_trans_form');
        $this->addSql('ALTER INDEX IF EXISTS UNIQ_ACF091E55FF69B7D4180C698 RENAME TO uniq_form_translation_locale');
        $this->addSql('ALTER INDEX IF EXISTS UNIQ_ACF091E54180C698989D9B62 RENAME TO uniq_form_translation_slug');
        $this->addSql('ALTER INDEX IF EXISTS IDX_C80AF9E65FF69B7D RENAME TO idx_8540af9f5ff69b7d');
    }
}
