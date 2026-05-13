<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Enum;

use Aurora\Core\Sequence\SequencePrefixEnum;

enum ApplicationParameterEnum: string implements ApplicationParameterEnumInterface
{
    case SiteName = 'site_name';
    case SiteDescription = 'site_description';
    case SiteUrl = 'site_url';
    case AdminEmail = 'backend_email';
    case DefaultLocale = 'default_locale';
    case PostsPerPage = 'posts_per_page';
    case MaxUploadSizeMb = 'max_upload_size_mb';
    case AllowedUploadExtensions = 'allowed_upload_extensions';
    case Timezone = 'timezone';
    case DateFormat = 'date_format';
    case CommentsEnabled = 'comments_enabled';
    case CommentModerationEnabled = 'comment_moderation_enabled';
    case MaintenanceMode = 'maintenance_mode';
    case AdminRegistrationEnabled = 'backend_registration_enabled';
    case AdminAccessRequestEnabled = 'backend_access_request_enabled';
    case FrontRegistrationEnabled = 'frontend_registration_enabled';
    case PostRevisionsLimit = 'post_revisions_limit';
    case TrashAutoPurgeDays = 'trash_auto_purge_days';
    case HomepagePostId = 'homepage_post_id';
    case DefaultFront = 'default_front';
    case LogoMediaId = 'logo_media_id';
    case FaviconMediaId = 'favicon_media_id';
    case SeoTitleTemplate = 'seo_title_template';
    case SeoDefaultDescription = 'seo_default_description';
    case EcommerceLowStockThreshold = 'backend_ecommerce_low_stock_threshold';
    case GedDocumentPrefix = 'backend_ged_document_prefix';
    case PdfFormDocumentPrefix = 'backend_pdfform_document_prefix';
    case BillingInvoicePrefix = 'backend_billing_invoice_prefix';
    case BillingCreditNotePrefix = 'backend_billing_credit_note_prefix';
    case EcommerceOrderPrefix = 'backend_ecommerce_order_prefix';
    case EcommerceListingPrefix = 'backend_ecommerce_listing_prefix';
    case ErpProductPrefix = 'backend_erp_product_prefix';
    case CrmDealPrefix = 'backend_crm_deal_prefix';
    case CrmContactPrefix = 'backend_crm_contact_prefix';
    case CrmCompanyPrefix = 'backend_crm_company_prefix';
    case EmailLocale = 'email_locale';
    case PhotoGalleryPrefix = 'photo_gallery_prefix';
    case EditorialPostPrefix = 'editorial_post_prefix';
    case EditorialFormPrefix = 'editorial_form_prefix';
    case BillingTiersPrefix = 'backend_billing_tiers_prefix';
    case CoreUserPrefix = 'core_user_prefix';
    case CoreMediaPrefix = 'core_media_prefix';
    case CoreAccessRequestPrefix = 'core_access_request_prefix';
    case EditorialFormSubmissionPrefix = 'editorial_form_submission_prefix';
    case PhotoGalleryItemPrefix = 'photo_gallery_item_prefix';
    case PhotoGalleryInvitePrefix = 'photo_gallery_invite_prefix';
    case EditorialCommentPrefix = 'editorial_comment_prefix';
    case CoreAuditLogPrefix = 'core_audit_log_prefix';
    case CoreResetPasswordPrefix = 'core_reset_password_prefix';
    case CoreMediaFolderPrefix = 'core_media_folder_prefix';
    case CoreMenuItemPrefix = 'core_menu_item_prefix';
    case BillingOcrJobPrefix = 'backend_billing_ocr_job_prefix';
    case EcommerceCartPrefix = 'backend_ecommerce_cart_prefix';
    case EcommerceCartItemPrefix = 'backend_ecommerce_cart_item_prefix';
    case EcommerceOrderLinePrefix = 'backend_ecommerce_order_line_prefix';
    case EditorialFormFieldPrefix = 'editorial_form_field_prefix';
    case EditorialTaxonomyTermPrefix = 'editorial_taxonomy_term_prefix';
    case PhotoGalleryFinalizationPrefix = 'photo_gallery_finalization_prefix';
    case PhotoGalleryItemCommentPrefix = 'photo_gallery_item_comment_prefix';
    case PhotoGalleryPickPrefix = 'photo_gallery_pick_prefix';
    case NavSectionAliases = 'nav_section_aliases';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SiteName => 'backend.parameters.site_name.label',
            self::SiteDescription => 'backend.parameters.site_description.label',
            self::SiteUrl => 'backend.parameters.site_url.label',
            self::AdminEmail => 'backend.parameters.admin_email.label',
            self::DefaultLocale => 'backend.parameters.default_locale.label',
            self::PostsPerPage => 'backend.parameters.posts_per_page.label',
            self::MaxUploadSizeMb => 'backend.parameters.max_upload_size_mb.label',
            self::AllowedUploadExtensions => 'backend.parameters.allowed_upload_extensions.label',
            self::Timezone => 'backend.parameters.timezone.label',
            self::DateFormat => 'backend.parameters.date_format.label',
            self::CommentsEnabled => 'backend.parameters.comments_enabled.label',
            self::CommentModerationEnabled => 'backend.parameters.comment_moderation_enabled.label',
            self::MaintenanceMode => 'backend.parameters.maintenance_mode.label',
            self::AdminRegistrationEnabled => 'backend.parameters.admin_registration_enabled.label',
            self::AdminAccessRequestEnabled => 'backend.parameters.admin_access_request_enabled.label',
            self::FrontRegistrationEnabled => 'backend.parameters.front_registration_enabled.label',
            self::PostRevisionsLimit => 'backend.parameters.post_revisions_limit.label',
            self::TrashAutoPurgeDays => 'backend.parameters.trash_auto_purge_days.label',
            self::HomepagePostId => 'backend.parameters.homepage_post_id.label',
            self::DefaultFront => 'backend.parameters.default_front.label',
            self::LogoMediaId => 'backend.parameters.logo_media_id.label',
            self::FaviconMediaId => 'backend.parameters.favicon_media_id.label',
            self::SeoTitleTemplate => 'backend.parameters.seo_title_template.label',
            self::SeoDefaultDescription => 'backend.parameters.seo_default_description.label',
            self::EcommerceLowStockThreshold => 'backend.parameters.ecommerce_low_stock_threshold.label',
            self::GedDocumentPrefix => 'backend.parameters.ged_document_prefix.label',
            self::PdfFormDocumentPrefix => 'backend.parameters.pdfform_document_prefix.label',
            self::BillingInvoicePrefix => 'backend.parameters.billing_invoice_prefix.label',
            self::BillingCreditNotePrefix => 'backend.parameters.billing_credit_note_prefix.label',
            self::EcommerceOrderPrefix => 'backend.parameters.ecommerce_order_prefix.label',
            self::EcommerceListingPrefix => 'backend.parameters.ecommerce_listing_prefix.label',
            self::ErpProductPrefix => 'backend.parameters.erp_product_prefix.label',
            self::CrmDealPrefix => 'backend.parameters.crm_deal_prefix.label',
            self::CrmContactPrefix => 'backend.parameters.crm_contact_prefix.label',
            self::CrmCompanyPrefix => 'backend.parameters.crm_company_prefix.label',
            self::EmailLocale => 'backend.parameters.email_locale.label',
            self::PhotoGalleryPrefix => 'backend.parameters.photo_gallery_prefix.label',
            self::EditorialPostPrefix => 'backend.parameters.editorial_post_prefix.label',
            self::EditorialFormPrefix => 'backend.parameters.editorial_form_prefix.label',
            self::BillingTiersPrefix => 'backend.parameters.billing_tiers_prefix.label',
            self::CoreUserPrefix => 'backend.parameters.core_user_prefix.label',
            self::CoreMediaPrefix => 'backend.parameters.core_media_prefix.label',
            self::CoreAccessRequestPrefix => 'backend.parameters.core_access_request_prefix.label',
            self::EditorialFormSubmissionPrefix => 'backend.parameters.editorial_form_submission_prefix.label',
            self::PhotoGalleryItemPrefix => 'backend.parameters.photo_gallery_item_prefix.label',
            self::PhotoGalleryInvitePrefix => 'backend.parameters.photo_gallery_invite_prefix.label',
            self::EditorialCommentPrefix => 'backend.parameters.editorial_comment_prefix.label',
            self::CoreAuditLogPrefix => 'backend.parameters.core_audit_log_prefix.label',
            self::CoreResetPasswordPrefix => 'backend.parameters.core_reset_password_prefix.label',
            self::CoreMediaFolderPrefix => 'backend.parameters.core_media_folder_prefix.label',
            self::CoreMenuItemPrefix => 'backend.parameters.core_menu_item_prefix.label',
            self::BillingOcrJobPrefix => 'backend.parameters.billing_ocr_job_prefix.label',
            self::EcommerceCartPrefix => 'backend.parameters.ecommerce_cart_prefix.label',
            self::EcommerceCartItemPrefix => 'backend.parameters.ecommerce_cart_item_prefix.label',
            self::EcommerceOrderLinePrefix => 'backend.parameters.ecommerce_order_line_prefix.label',
            self::EditorialFormFieldPrefix => 'backend.parameters.editorial_form_field_prefix.label',
            self::EditorialTaxonomyTermPrefix => 'backend.parameters.editorial_taxonomy_term_prefix.label',
            self::PhotoGalleryFinalizationPrefix => 'backend.parameters.photo_gallery_finalization_prefix.label',
            self::PhotoGalleryItemCommentPrefix => 'backend.parameters.photo_gallery_item_comment_prefix.label',
            self::PhotoGalleryPickPrefix => 'backend.parameters.photo_gallery_pick_prefix.label',
            self::NavSectionAliases => 'backend.parameters.nav_section_aliases.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SiteName => 'backend.parameters.site_name.description',
            self::SiteDescription => 'backend.parameters.site_description.description',
            self::SiteUrl => 'backend.parameters.site_url.description',
            self::AdminEmail => 'backend.parameters.admin_email.description',
            self::DefaultLocale => 'backend.parameters.default_locale.description',
            self::PostsPerPage => 'backend.parameters.posts_per_page.description',
            self::MaxUploadSizeMb => 'backend.parameters.max_upload_size_mb.description',
            self::AllowedUploadExtensions => 'backend.parameters.allowed_upload_extensions.description',
            self::Timezone => 'backend.parameters.timezone.description',
            self::DateFormat => 'backend.parameters.date_format.description',
            self::CommentsEnabled => 'backend.parameters.comments_enabled.description',
            self::CommentModerationEnabled => 'backend.parameters.comment_moderation_enabled.description',
            self::MaintenanceMode => 'backend.parameters.maintenance_mode.description',
            self::AdminRegistrationEnabled => 'backend.parameters.admin_registration_enabled.description',
            self::AdminAccessRequestEnabled => 'backend.parameters.admin_access_request_enabled.description',
            self::FrontRegistrationEnabled => 'backend.parameters.front_registration_enabled.description',
            self::PostRevisionsLimit => 'backend.parameters.post_revisions_limit.description',
            self::TrashAutoPurgeDays => 'backend.parameters.trash_auto_purge_days.description',
            self::HomepagePostId => 'backend.parameters.homepage_post_id.description',
            self::DefaultFront => 'backend.parameters.default_front.description',
            self::LogoMediaId => 'backend.parameters.logo_media_id.description',
            self::FaviconMediaId => 'backend.parameters.favicon_media_id.description',
            self::SeoTitleTemplate => 'backend.parameters.seo_title_template.description',
            self::SeoDefaultDescription => 'backend.parameters.seo_default_description.description',
            self::EcommerceLowStockThreshold => 'backend.parameters.ecommerce_low_stock_threshold.description',
            self::BillingInvoicePrefix => 'backend.parameters.billing_invoice_prefix.description',
            self::BillingCreditNotePrefix => 'backend.parameters.billing_credit_note_prefix.description',
            self::EcommerceListingPrefix => 'backend.parameters.ecommerce_listing_prefix.description',
            self::ErpProductPrefix => 'backend.parameters.erp_product_prefix.description',
            self::CrmDealPrefix => 'backend.parameters.crm_deal_prefix.description',
            self::CrmContactPrefix => 'backend.parameters.crm_contact_prefix.description',
            self::CrmCompanyPrefix => 'backend.parameters.crm_company_prefix.description',
            self::EcommerceOrderPrefix => 'backend.parameters.ecommerce_order_prefix.description',
            self::GedDocumentPrefix => 'backend.parameters.ged_document_prefix.description',
            self::PdfFormDocumentPrefix => 'backend.parameters.pdfform_document_prefix.description',
            self::EmailLocale => 'backend.parameters.email_locale.description',
            self::PhotoGalleryPrefix => 'backend.parameters.photo_gallery_prefix.description',
            self::EditorialPostPrefix => 'backend.parameters.editorial_post_prefix.description',
            self::EditorialFormPrefix => 'backend.parameters.editorial_form_prefix.description',
            self::BillingTiersPrefix => 'backend.parameters.billing_tiers_prefix.description',
            self::CoreUserPrefix => 'backend.parameters.core_user_prefix.description',
            self::CoreMediaPrefix => 'backend.parameters.core_media_prefix.description',
            self::CoreAccessRequestPrefix => 'backend.parameters.core_access_request_prefix.description',
            self::EditorialFormSubmissionPrefix => 'backend.parameters.editorial_form_submission_prefix.description',
            self::PhotoGalleryItemPrefix => 'backend.parameters.photo_gallery_item_prefix.description',
            self::PhotoGalleryInvitePrefix => 'backend.parameters.photo_gallery_invite_prefix.description',
            self::EditorialCommentPrefix => 'backend.parameters.editorial_comment_prefix.description',
            self::CoreAuditLogPrefix => 'backend.parameters.core_audit_log_prefix.description',
            self::CoreResetPasswordPrefix => 'backend.parameters.core_reset_password_prefix.description',
            self::CoreMediaFolderPrefix => 'backend.parameters.core_media_folder_prefix.description',
            self::CoreMenuItemPrefix => 'backend.parameters.core_menu_item_prefix.description',
            self::BillingOcrJobPrefix => 'backend.parameters.billing_ocr_job_prefix.description',
            self::EcommerceCartPrefix => 'backend.parameters.ecommerce_cart_prefix.description',
            self::EcommerceCartItemPrefix => 'backend.parameters.ecommerce_cart_item_prefix.description',
            self::EcommerceOrderLinePrefix => 'backend.parameters.ecommerce_order_line_prefix.description',
            self::EditorialFormFieldPrefix => 'backend.parameters.editorial_form_field_prefix.description',
            self::EditorialTaxonomyTermPrefix => 'backend.parameters.editorial_taxonomy_term_prefix.description',
            self::PhotoGalleryFinalizationPrefix => 'backend.parameters.photo_gallery_finalization_prefix.description',
            self::PhotoGalleryItemCommentPrefix => 'backend.parameters.photo_gallery_item_comment_prefix.description',
            self::PhotoGalleryPickPrefix => 'backend.parameters.photo_gallery_pick_prefix.description',
            self::NavSectionAliases => 'backend.parameters.nav_section_aliases.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::SiteName => 'Aurora',
            self::SiteDescription => 'Propulsé par Aurora',
            self::SiteUrl => 'http://localhost',
            self::AdminEmail => 'admin@aurora.app',
            self::DefaultLocale => 'fr',
            self::PostsPerPage => '10',
            self::MaxUploadSizeMb => '20',
            self::AllowedUploadExtensions => 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,zip',
            self::Timezone => 'Europe/Paris',
            self::DateFormat => 'd/m/Y',
            self::CommentsEnabled => '1',
            self::CommentModerationEnabled => '1',
            self::MaintenanceMode => '0',
            self::AdminRegistrationEnabled => '0',
            self::AdminAccessRequestEnabled => '1',
            self::FrontRegistrationEnabled => '0',
            self::PostRevisionsLimit => '20',
            self::TrashAutoPurgeDays => '30',
            self::HomepagePostId => '',
            self::DefaultFront => 'editorial',
            self::LogoMediaId => '',
            self::FaviconMediaId => '',
            self::SeoTitleTemplate => '{title} — {siteName}',
            self::SeoDefaultDescription => '',
            self::EcommerceLowStockThreshold => '5',
            self::GedDocumentPrefix => 'DOC',
            self::PdfFormDocumentPrefix => SequencePrefixEnum::PdfFormDocument->value,
            self::BillingInvoicePrefix => SequencePrefixEnum::Invoice->value,
            self::BillingCreditNotePrefix => SequencePrefixEnum::CreditNote->value,
            self::EcommerceOrderPrefix => SequencePrefixEnum::Order->value,
            self::EcommerceListingPrefix => SequencePrefixEnum::Listing->value,
            self::ErpProductPrefix => SequencePrefixEnum::Product->value,
            self::CrmDealPrefix => SequencePrefixEnum::Deal->value,
            self::CrmContactPrefix => SequencePrefixEnum::Contact->value,
            self::CrmCompanyPrefix => SequencePrefixEnum::Company->value,
            self::EmailLocale => '',
            self::PhotoGalleryPrefix => SequencePrefixEnum::Gallery->value,
            self::EditorialPostPrefix => SequencePrefixEnum::Post->value,
            self::EditorialFormPrefix => SequencePrefixEnum::Form->value,
            self::BillingTiersPrefix => SequencePrefixEnum::Tiers->value,
            self::CoreUserPrefix => SequencePrefixEnum::User->value,
            self::CoreMediaPrefix => SequencePrefixEnum::Media->value,
            self::CoreAccessRequestPrefix => SequencePrefixEnum::AccessRequest->value,
            self::EditorialFormSubmissionPrefix => SequencePrefixEnum::FormSubmission->value,
            self::PhotoGalleryItemPrefix => SequencePrefixEnum::GalleryItem->value,
            self::PhotoGalleryInvitePrefix => SequencePrefixEnum::GalleryInvite->value,
            self::EditorialCommentPrefix => SequencePrefixEnum::Comment->value,
            self::CoreAuditLogPrefix => SequencePrefixEnum::AuditLog->value,
            self::CoreResetPasswordPrefix => SequencePrefixEnum::ResetPasswordRequest->value,
            self::CoreMediaFolderPrefix => SequencePrefixEnum::MediaFolder->value,
            self::CoreMenuItemPrefix => SequencePrefixEnum::MenuItem->value,
            self::BillingOcrJobPrefix => SequencePrefixEnum::OcrJob->value,
            self::EcommerceCartPrefix => SequencePrefixEnum::Cart->value,
            self::EcommerceCartItemPrefix => SequencePrefixEnum::CartItem->value,
            self::EcommerceOrderLinePrefix => SequencePrefixEnum::OrderLine->value,
            self::EditorialFormFieldPrefix => SequencePrefixEnum::FormField->value,
            self::EditorialTaxonomyTermPrefix => SequencePrefixEnum::TaxonomyTerm->value,
            self::PhotoGalleryFinalizationPrefix => SequencePrefixEnum::GalleryFinalization->value,
            self::PhotoGalleryItemCommentPrefix => SequencePrefixEnum::GalleryItemComment->value,
            self::PhotoGalleryPickPrefix => SequencePrefixEnum::GalleryPick->value,
            self::NavSectionAliases => '{}',
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::PostsPerPage, self::MaxUploadSizeMb, self::PostRevisionsLimit, self::TrashAutoPurgeDays, self::EcommerceLowStockThreshold => 'int',
            self::HomepagePostId => 'post',
            self::DefaultFront => 'select',
            self::CommentsEnabled, self::CommentModerationEnabled, self::MaintenanceMode, self::AdminRegistrationEnabled, self::AdminAccessRequestEnabled, self::FrontRegistrationEnabled => 'bool',
            self::BillingInvoicePrefix, self::BillingCreditNotePrefix, self::EcommerceOrderPrefix, self::EcommerceListingPrefix, self::ErpProductPrefix, self::CrmDealPrefix, self::CrmContactPrefix, self::CrmCompanyPrefix => 'string',
            self::LogoMediaId, self::FaviconMediaId => 'media',
            default => 'string',
        };
    }

    public function isAdminAccessible(): bool
    {
        return match ($this->getGroup()) {
            'general', 'reading', 'localization', 'branding', 'seo', 'system', 'ecommerce', 'email', 'sequences', 'media', 'navigation' => true,
            default => false,
        };
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::SiteName, self::SiteDescription, self::SiteUrl, self::AdminEmail => 'general',
            self::DefaultLocale, self::Timezone, self::DateFormat => 'localization',
            self::PostsPerPage, self::CommentsEnabled, self::CommentModerationEnabled, self::PostRevisionsLimit, self::TrashAutoPurgeDays, self::HomepagePostId, self::DefaultFront => 'reading',
            self::MaxUploadSizeMb, self::AllowedUploadExtensions => 'media',
            self::MaintenanceMode, self::AdminRegistrationEnabled, self::AdminAccessRequestEnabled, self::FrontRegistrationEnabled => 'system',
            self::LogoMediaId, self::FaviconMediaId => 'branding',
            self::SeoTitleTemplate, self::SeoDefaultDescription => 'seo',
            self::BillingInvoicePrefix, self::BillingCreditNotePrefix, self::EcommerceOrderPrefix, self::EcommerceListingPrefix, self::ErpProductPrefix, self::CrmDealPrefix, self::CrmContactPrefix, self::CrmCompanyPrefix, self::PhotoGalleryPrefix, self::EditorialPostPrefix, self::EditorialFormPrefix, self::BillingTiersPrefix, self::CoreUserPrefix, self::CoreMediaPrefix, self::CoreAccessRequestPrefix, self::EditorialFormSubmissionPrefix, self::PhotoGalleryItemPrefix, self::PhotoGalleryInvitePrefix, self::EditorialCommentPrefix, self::CoreAuditLogPrefix, self::CoreResetPasswordPrefix, self::CoreMediaFolderPrefix, self::CoreMenuItemPrefix, self::BillingOcrJobPrefix, self::EcommerceCartPrefix, self::EcommerceCartItemPrefix, self::EcommerceOrderLinePrefix, self::EditorialFormFieldPrefix, self::EditorialTaxonomyTermPrefix, self::PhotoGalleryFinalizationPrefix, self::PhotoGalleryItemCommentPrefix, self::PhotoGalleryPickPrefix, self::GedDocumentPrefix, self::PdfFormDocumentPrefix => 'sequences',
            self::EcommerceLowStockThreshold => 'ecommerce',
            self::EmailLocale => 'email',
            self::NavSectionAliases => 'navigation',
        };
    }
}
