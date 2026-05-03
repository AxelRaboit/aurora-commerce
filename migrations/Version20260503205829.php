<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503205829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE photo_gallery_item_comments_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE photo_gallery_finalizations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE photo_gallery_invites_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE seq_access_request_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_audit_log_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_invoice_line_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_invoice_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_ocr_job_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_tiers_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_comment_reaction_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_comment_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_company_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_contact_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_deal_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_cart_item_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_cart_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_listing_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_order_line_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_order_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_product_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_form_field_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_form_field_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_form_submission_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_form_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_form_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_media_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_media_folder_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_menu_item_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_menu_item_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_menu_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_gallery_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_gallery_finalization_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_gallery_invite_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_gallery_item_comment_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_gallery_item_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_gallery_pick_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_post_revision_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_post_slug_history_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_post_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_post_type_field_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_post_type_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_post_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_reset_password_request_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_taxonomy_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_taxonomy_term_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_taxonomy_term_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_taxonomy_translation_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_theme_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_user_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE access_requests ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE audit_logs ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE billing_invoice_lines ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE billing_invoices ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE billing_ocr_jobs ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE billing_tiers ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE comment_reactions ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE comments ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE crm_companies ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE crm_contacts ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE crm_deals ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_cart_items ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_carts ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_listings ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_order_lines ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE erp_products ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE form_field_translations ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE form_fields ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE form_submissions ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE form_translations ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE forms ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE media ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE media_folders ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE menu_item_translations ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE menu_items ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE menus ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE photo_galleries ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_finalizations ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_invites ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_item_comments ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_items ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_picks ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE post_revisions ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE post_slug_history ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE post_translations ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE post_type_fields ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE post_types ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE posts ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE reset_password_requests ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE taxonomies ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE taxonomy_term_translations ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE taxonomy_terms ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE taxonomy_translations ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE themes ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE users ALTER id DROP IDENTITY');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_access_request_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_audit_log_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_invoice_line_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_invoice_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_ocr_job_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_tiers_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_comment_reaction_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_comment_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_company_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_contact_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_deal_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_cart_item_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_cart_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_listing_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_order_line_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_order_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_product_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_form_field_translation_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_form_field_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_form_submission_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_form_translation_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_form_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_media_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_media_folder_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_menu_item_translation_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_menu_item_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_menu_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_gallery_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_gallery_finalization_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_gallery_invite_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_gallery_item_comment_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_gallery_item_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_gallery_pick_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_post_revision_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_post_slug_history_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_post_translation_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_post_type_field_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_post_type_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_post_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_reset_password_request_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_taxonomy_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_taxonomy_term_translation_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_taxonomy_term_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_taxonomy_translation_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_theme_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_user_id CASCADE');
        $this->addSql('CREATE SEQUENCE photo_gallery_item_comments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE photo_gallery_finalizations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE photo_gallery_invites_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE access_requests ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE audit_logs ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE billing_invoice_lines ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE billing_invoices ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE billing_ocr_jobs ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE billing_tiers ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE comment_reactions ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE comments ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE crm_companies ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE crm_contacts ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE crm_deals ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_cart_items ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_carts ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_listings ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_order_lines ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE ecommerce_orders ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE erp_products ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE form_field_translations ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE form_fields ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE form_submissions ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE form_translations ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE forms ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE media ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE media_folders ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE menu_item_translations ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE menu_items ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE menus ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE photo_galleries ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_finalizations ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_invites ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_item_comments ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_items ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE photo_gallery_picks ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE post_revisions ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE post_slug_history ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE post_translations ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE post_type_fields ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE post_types ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE posts ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE reset_password_requests ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE taxonomies ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE taxonomy_term_translations ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE taxonomy_terms ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE taxonomy_translations ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE themes ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE users ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
    }
}
