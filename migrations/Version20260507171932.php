<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507171932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add core_ prefix to all Aurora tables to prevent conflicts with client tables';
    }

    /** @var array<string, string> old → new */
    private const array RENAMES = [
        'access_requests' => 'core_access_requests',
        'agencies' => 'core_agencies',
        'audit_logs' => 'core_audit_logs',
        'billing_invoice_lines' => 'core_billing_invoice_lines',
        'billing_invoices' => 'core_billing_invoices',
        'billing_ocr_jobs' => 'core_billing_ocr_jobs',
        'billing_tiers' => 'core_billing_tiers',
        'comment_reactions' => 'core_comment_reactions',
        'comments' => 'core_comments',
        'crm_companies' => 'core_crm_companies',
        'crm_contacts' => 'core_crm_contacts',
        'crm_deals' => 'core_crm_deals',
        'ecommerce_cart_items' => 'core_ecommerce_cart_items',
        'ecommerce_carts' => 'core_ecommerce_carts',
        'ecommerce_listings' => 'core_ecommerce_listings',
        'ecommerce_order_lines' => 'core_ecommerce_order_lines',
        'ecommerce_orders' => 'core_ecommerce_orders',
        'erp_products' => 'core_erp_products',
        'form_field_translations' => 'core_form_field_translations',
        'form_fields' => 'core_form_fields',
        'form_submissions' => 'core_form_submissions',
        'form_translations' => 'core_form_translations',
        'forms' => 'core_forms',
        'ged_document_categories' => 'core_ged_document_categories',
        'ged_documents' => 'core_ged_documents',
        'locales' => 'core_locales',
        'media' => 'core_media',
        'media_folders' => 'core_media_folders',
        'menu_item_translations' => 'core_menu_item_translations',
        'menu_items' => 'core_menu_items',
        'menus' => 'core_menus',
        'photo_galleries' => 'core_photo_galleries',
        'photo_gallery_finalizations' => 'core_photo_gallery_finalizations',
        'photo_gallery_invites' => 'core_photo_gallery_invites',
        'photo_gallery_item_comments' => 'core_photo_gallery_item_comments',
        'photo_gallery_items' => 'core_photo_gallery_items',
        'photo_gallery_picks' => 'core_photo_gallery_picks',
        'post_related_posts' => 'core_post_related_posts',
        'post_revisions' => 'core_post_revisions',
        'post_slug_history' => 'core_post_slug_history',
        'post_terms' => 'core_post_terms',
        'post_translations' => 'core_post_translations',
        'post_type_fields' => 'core_post_type_fields',
        'post_type_taxonomies' => 'core_post_type_taxonomies',
        'post_types' => 'core_post_types',
        'posts' => 'core_posts',
        'reset_password_requests' => 'core_reset_password_requests',
        'services' => 'core_services',
        'settings' => 'core_settings',
        'taxonomies' => 'core_taxonomies',
        'taxonomy_term_translations' => 'core_taxonomy_term_translations',
        'taxonomy_terms' => 'core_taxonomy_terms',
        'taxonomy_translations' => 'core_taxonomy_translations',
        'themes' => 'core_themes',
        'users' => 'core_users',
    ];

    public function up(Schema $schema): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(sprintf('ALTER TABLE %s RENAME TO %s', $old, $new));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->addSql(sprintf('ALTER TABLE %s RENAME TO %s', $new, $old));
        }
    }
}
