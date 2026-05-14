<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Backfill ContactTag rows + join rows from the legacy JSON `tags` column on
 * core_crm_contacts, then drop the column. The data migration is performed
 * in PHP via the DBAL connection because reading JSON, deduplicating labels
 * and capturing the INSERT-returned ids would be cumbersome in pure SQL.
 */
final class Version20260514192843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate legacy Contact.tags JSON data into ContactTag rows + join table, then drop the JSON column';
    }

    public function up(Schema $schema): void
    {
        $this->migrateContactTags();

        $this->addSql('ALTER TABLE core_crm_contacts DROP tags');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE core_crm_contacts ADD tags JSON DEFAULT '[]' NOT NULL");
    }

    private function migrateContactTags(): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, tags FROM core_crm_contacts WHERE tags IS NOT NULL');
        if ([] === $rows) {
            return;
        }

        $slugger = new AsciiSlugger();

        $existingByLabel = [];
        $existingTagRows = $this->connection->fetchAllAssociative('SELECT id, label FROM core_crm_contact_tags');
        foreach ($existingTagRows as $existingTagRow) {
            $existingByLabel[(string) $existingTagRow['label']] = (int) $existingTagRow['id'];
        }

        $tagIdByLabel = $existingByLabel;
        $usedSlugs = [];
        foreach ($this->connection->fetchAllAssociative('SELECT slug FROM core_crm_contact_tags') as $slugRow) {
            $usedSlugs[(string) $slugRow['slug']] = true;
        }

        $contactTagPairs = [];
        foreach ($rows as $row) {
            $contactId = (int) $row['id'];
            $labels = json_decode((string) ($row['tags'] ?? '[]'), true) ?? [];
            if (!is_array($labels)) {
                continue;
            }

            foreach ($labels as $rawLabel) {
                $label = mb_trim((string) $rawLabel);
                if ('' === $label) {
                    continue;
                }

                if (!isset($tagIdByLabel[$label])) {
                    $baseSlug = $slugger->slug($label)->lower()->toString();
                    $slug = $baseSlug;
                    $suffix = 2;
                    while (isset($usedSlugs[$slug])) {
                        $slug = $baseSlug.'-'.$suffix;
                        ++$suffix;
                    }
                    $usedSlugs[$slug] = true;

                    $newId = (int) $this->connection->fetchOne(
                        "INSERT INTO core_crm_contact_tags (id, label, slug, color, created_at, updated_at) VALUES (NEXTVAL('seq_core_contact_tag_id'), :label, :slug, :color, NOW(), NOW()) RETURNING id",
                        ['label' => $label, 'slug' => $slug, 'color' => '#6366F1'],
                    );
                    $tagIdByLabel[$label] = $newId;
                }

                $contactTagPairs[$contactId.'-'.$tagIdByLabel[$label]] = [$contactId, $tagIdByLabel[$label]];
            }
        }

        foreach ($contactTagPairs as [$contactId, $contactTagId]) {
            $this->connection->executeStatement(
                'INSERT INTO core_crm_contact_tag_map (contact_id, contact_tag_id) VALUES (:contact_id, :contact_tag_id) ON CONFLICT DO NOTHING',
                ['contact_id' => $contactId, 'contact_tag_id' => $contactTagId],
            );
        }
    }
}
