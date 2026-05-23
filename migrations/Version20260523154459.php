<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523154459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add User.nav_section_colors — per-user palette overrides for sidemenu sections (JSON map of sectionId → Tailwind colour name).';
    }

    public function up(Schema $schema): void
    {
        // Diff-generated DROP/RECREATE of `uniq_pf_goal_user_category_wallet`
        // is a false positive — the index uses COALESCE(wallet_id, 0) which
        // Doctrine's schema comparator can't read. Skip the noise.
        $this->addSql("ALTER TABLE core_users ADD nav_section_colors JSON DEFAULT '{}' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_users DROP nav_section_colors');
    }
}
