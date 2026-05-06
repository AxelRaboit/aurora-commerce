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
    case EcommerceAdminEnabled = 'backend_ecommerce_admin_enabled';
    case EcommerceFrontEnabled = 'backend_ecommerce_front_enabled';
    case EcommerceLowStockThreshold = 'backend_ecommerce_low_stock_threshold';
    case CrmAdminEnabled = 'backend_crm_admin_enabled';
    case ErpAdminEnabled = 'backend_erp_admin_enabled';
    case PhotoAdminEnabled = 'photo_admin_enabled';
    case PhotoFrontEnabled = 'photo_front_enabled';
    case BillingAdminEnabled = 'backend_billing_admin_enabled';
    case GedAdminEnabled = 'backend_ged_admin_enabled';
    case GedDocumentPrefix = 'backend_ged_document_prefix';
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

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SiteName => 'Nom du site',
            self::SiteDescription => 'Description du site',
            self::SiteUrl => 'URL publique du site',
            self::AdminEmail => 'Email administrateur',
            self::DefaultLocale => 'Langue par défaut',
            self::PostsPerPage => 'Articles par page',
            self::MaxUploadSizeMb => "Taille max d'upload (Mo)",
            self::AllowedUploadExtensions => 'Extensions autorisées',
            self::Timezone => 'Fuseau horaire',
            self::DateFormat => "Format d'affichage des dates",
            self::CommentsEnabled => 'Commentaires activés',
            self::CommentModerationEnabled => 'Modération des commentaires',
            self::MaintenanceMode => 'Mode maintenance',
            self::AdminRegistrationEnabled => 'Inscriptions admin ouvertes',
            self::AdminAccessRequestEnabled => "Demandes d'accès admin ouvertes",
            self::FrontRegistrationEnabled => 'Inscriptions front ouvertes',
            self::PostRevisionsLimit => 'Nombre de révisions gardées par article',
            self::TrashAutoPurgeDays => 'Purge auto de la corbeille (jours)',
            self::HomepagePostId => "Page d'accueil (ID du post)",
            self::DefaultFront => 'Front par défaut',
            self::LogoMediaId => 'Logo du site',
            self::FaviconMediaId => 'Favicon',
            self::SeoTitleTemplate => 'Template de titre SEO',
            self::SeoDefaultDescription => 'Description SEO par défaut',
            self::EcommerceAdminEnabled => 'Administration e-commerce activée',
            self::EcommerceFrontEnabled => 'Boutique e-commerce activée (front)',
            self::EcommerceLowStockThreshold => 'Seuil de stock bas (e-commerce)',
            self::CrmAdminEnabled => 'Administration CRM activée',
            self::ErpAdminEnabled => 'Administration ERP activée',
            self::PhotoAdminEnabled => 'Administration Photo activée',
            self::PhotoFrontEnabled => 'Galeries publiques activées (front)',
            self::BillingAdminEnabled => 'Administration facturation activée',
            self::GedAdminEnabled => 'Administration GED activée',
            self::GedDocumentPrefix => 'Préfixe des références documents GED',
            self::BillingInvoicePrefix => 'Préfixe des numéros de factures',
            self::BillingCreditNotePrefix => 'Préfixe des numéros d\'avoirs',
            self::EcommerceOrderPrefix => 'Préfixe des numéros de commandes',
            self::EcommerceListingPrefix => 'Préfixe des références produits boutique',
            self::ErpProductPrefix => 'Préfixe des références produits ERP',
            self::CrmDealPrefix => 'Préfixe des références affaires CRM',
            self::CrmContactPrefix => 'Préfixe des références contacts CRM',
            self::CrmCompanyPrefix => 'Préfixe des références entreprises CRM',
            self::EmailLocale => 'Langue forcée pour les emails',
            self::PhotoGalleryPrefix => 'Préfixe des numéros de galeries',
            self::EditorialPostPrefix => "Préfixe des numéros d'articles",
            self::EditorialFormPrefix => 'Préfixe des numéros de formulaires',
            self::BillingTiersPrefix => 'Préfixe des numéros de tiers',
            self::CoreUserPrefix => 'Préfixe des références utilisateurs',
            self::CoreMediaPrefix => 'Préfixe des références médias',
            self::CoreAccessRequestPrefix => "Préfixe des références demandes d'accès",
            self::EditorialFormSubmissionPrefix => 'Préfixe des numéros de soumissions',
            self::PhotoGalleryItemPrefix => 'Préfixe des références photos',
            self::PhotoGalleryInvitePrefix => 'Préfixe des références invitations galerie',
            self::EditorialCommentPrefix => 'Préfixe des numéros de commentaires',
            self::CoreAuditLogPrefix => 'Préfixe des logs d\'audit',
            self::CoreResetPasswordPrefix => 'Préfixe des demandes de réinitialisation de mot de passe',
            self::CoreMediaFolderPrefix => 'Préfixe des références dossiers médias',
            self::CoreMenuItemPrefix => 'Préfixe des références éléments de menu',
            self::BillingOcrJobPrefix => 'Préfixe des références jobs OCR',
            self::EcommerceCartPrefix => 'Préfixe des références paniers',
            self::EcommerceCartItemPrefix => 'Préfixe des références lignes de panier',
            self::EcommerceOrderLinePrefix => 'Préfixe des références lignes de commande',
            self::EditorialFormFieldPrefix => 'Préfixe des références champs de formulaire',
            self::EditorialTaxonomyTermPrefix => 'Préfixe des références termes de taxonomie',
            self::PhotoGalleryFinalizationPrefix => 'Préfixe des références finalisations de galerie',
            self::PhotoGalleryItemCommentPrefix => 'Préfixe des références commentaires de photo',
            self::PhotoGalleryPickPrefix => 'Préfixe des références sélections de photo',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SiteName => 'Nom affiché sur le site public et dans les emails',
            self::SiteDescription => 'Courte description utilisée comme meta description par défaut',
            self::SiteUrl => 'URL absolue du site (sans slash final), utilisée dans les emails et le sitemap',
            self::AdminEmail => "Adresse email de l'administrateur",
            self::DefaultLocale => 'Code de la langue par défaut (fr, en, es, de)',
            self::PostsPerPage => 'Nombre d\'articles affichés par page sur les listes publiques',
            self::MaxUploadSizeMb => 'Taille maximale autorisée pour un upload média, en mégaoctets',
            self::AllowedUploadExtensions => "Liste d'extensions autorisées séparées par virgule (jpg,png,pdf,…)",
            self::Timezone => 'Fuseau horaire PHP (ex: Europe/Paris)',
            self::DateFormat => "Format d'affichage des dates (ex: d/m/Y)",
            self::CommentsEnabled => 'Commentaires activés (0 = désactivés, 1 = activés)',
            self::CommentModerationEnabled => 'Si activée, les commentaires sont en attente de modération avant publication (1 = activée, 0 = approbation automatique)',
            self::MaintenanceMode => 'Mode maintenance (0 = désactivé, 1 = site fermé au public)',
            self::AdminRegistrationEnabled => 'Autoriser les inscriptions via /register (interface admin) — désactiver après la création du premier compte admin',
            self::AdminAccessRequestEnabled => "Autoriser les demandes d'accès via /access-request (formulaire de demande d'accès à l'administration)",
            self::FrontRegistrationEnabled => 'Autoriser les inscriptions publiques sur le front (0 = désactivé, 1 = activé)',
            self::PostRevisionsLimit => 'Nombre maximal de révisions conservées par article (les plus anciennes sont supprimées)',
            self::TrashAutoPurgeDays => 'Nombre de jours avant suppression définitive des articles en corbeille (0 = jamais)',
            self::HomepagePostId => 'ID d\'un post affiché sur la page d\'accueil. Vide = liste des derniers articles.',
            self::DefaultFront => 'Slug du front affiché par défaut sur "/". Ex: editorial, tracking.',
            self::LogoMediaId => 'ID du média utilisé comme logo',
            self::FaviconMediaId => 'ID du média utilisé comme favicon',
            self::SeoTitleTemplate => 'Template pour le titre des pages. Utilisez {title} et {siteName}. Ex: {title} — {siteName}',
            self::SeoDefaultDescription => 'Meta description utilisée quand aucune description spécifique n\'est définie',
            self::EcommerceAdminEnabled => "Active la section E-commerce dans l'administration (gestion des listings, commandes). Décocher cache la sidebar admin et 404 les routes /admin/ecommerce/*.",
            self::EcommerceFrontEnabled => 'Active la boutique côté site public (catalogue, fiche produit, panier, checkout, page commande client). Décocher 404 toutes les routes /shop, /cart, /checkout, /order/*.',
            self::EcommerceLowStockThreshold => "Affiche un avertissement « Plus que X en stock » sur la fiche produit quand le stock disponible est inférieur ou égal à cette valeur. Mettre 0 pour désactiver l'avertissement.",
            self::CrmAdminEnabled => "Active la section CRM dans l'administration (contacts, entreprises, affaires, kanban). Décocher cache la sidebar et 404 les routes /admin/crm/*.",
            self::ErpAdminEnabled => "Active la section ERP dans l'administration (produits). Décocher cache la sidebar et 404 les routes /admin/erp/*.",
            self::PhotoAdminEnabled => "Active la section Photographie dans l'administration (galeries de livraison client). Décocher cache la sidebar et 404 les routes /admin/galleries/*.",
            self::PhotoFrontEnabled => 'Active les galeries publiques côté front (pages /g/{slug}). Décocher 404 toutes les pages galerie pour les clients.',
            self::BillingInvoicePrefix => 'Préfixe du numéro de facture auto-généré à la validation (ex: FAC). Laissez vide pour désactiver.',
            self::BillingCreditNotePrefix => 'Préfixe du numéro d\'avoir auto-généré (ex: AV). Laissez vide pour désactiver.',
            self::EcommerceListingPrefix => 'Préfixe de la référence produit boutique auto-générée (ex: LST). Laissez vide pour désactiver.',
            self::ErpProductPrefix => 'Préfixe de la référence produit auto-générée (ex: PROD). Laissez vide pour désactiver.',
            self::CrmDealPrefix => 'Préfixe de la référence affaire CRM auto-générée (ex: DEAL). Laissez vide pour désactiver.',
            self::CrmContactPrefix => 'Préfixe de la référence contact CRM auto-générée (ex: CTT). Laissez vide pour désactiver.',
            self::CrmCompanyPrefix => 'Préfixe de la référence entreprise CRM auto-générée (ex: CPY). Laissez vide pour désactiver.',
            self::EcommerceOrderPrefix => 'Préfixe du numéro de commande auto-généré (ex: ORD). Laissez vide pour désactiver.',
            self::BillingAdminEnabled => "Active la section Facturation dans l'administration (factures fournisseurs, OCR, import). Décocher cache la sidebar et 404 les routes /admin/billing/*.",
            self::GedAdminEnabled => "Active la section GED dans l'administration. Décocher cache la sidebar et 404 les routes /admin/ged/*.",
            self::GedDocumentPrefix => 'Préfixe de la référence document GED auto-générée (ex: DOC). Laissez vide pour désactiver.',
            self::EmailLocale => 'Code langue (fr, en, es, de) à forcer pour tous les emails sortants. Vide = utiliser la langue de la requête courante.',
            self::PhotoGalleryPrefix => 'Préfixe de la référence galerie photo auto-générée (ex: GAL).',
            self::EditorialPostPrefix => 'Préfixe de la référence article auto-générée (ex: ART).',
            self::EditorialFormPrefix => 'Préfixe de la référence formulaire auto-générée (ex: FRM).',
            self::BillingTiersPrefix => 'Préfixe de la référence tiers (fournisseur/client) auto-générée (ex: TRS).',
            self::CoreUserPrefix,
            self::CoreMediaPrefix,
            self::CoreAccessRequestPrefix,
            self::EditorialFormSubmissionPrefix,
            self::PhotoGalleryItemPrefix,
            self::PhotoGalleryInvitePrefix,
            self::EditorialCommentPrefix,
            self::CoreAuditLogPrefix,
            self::CoreResetPasswordPrefix,
            self::CoreMediaFolderPrefix,
            self::CoreMenuItemPrefix,
            self::BillingOcrJobPrefix,
            self::EcommerceCartPrefix,
            self::EcommerceCartItemPrefix,
            self::EcommerceOrderLinePrefix,
            self::EditorialFormFieldPrefix,
            self::EditorialTaxonomyTermPrefix,
            self::PhotoGalleryFinalizationPrefix,
            self::PhotoGalleryItemCommentPrefix,
            self::PhotoGalleryPickPrefix => 'Préfixe de la référence auto-générée (ex: XXX). Laissez vide pour désactiver.',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::SiteName => 'Mon site Aurora',
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
            self::EcommerceAdminEnabled => '1',
            self::EcommerceFrontEnabled => '1',
            self::EcommerceLowStockThreshold => '5',
            self::CrmAdminEnabled => '1',
            self::ErpAdminEnabled => '1',
            self::PhotoAdminEnabled => '1',
            self::PhotoFrontEnabled => '1',
            self::BillingAdminEnabled => '1',
            self::GedAdminEnabled => '1',
            self::GedDocumentPrefix => 'DOC',
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
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::PostsPerPage, self::MaxUploadSizeMb, self::PostRevisionsLimit, self::TrashAutoPurgeDays, self::EcommerceLowStockThreshold => 'int',
            self::HomepagePostId => 'post',
            self::DefaultFront => 'select',
            self::CommentsEnabled, self::CommentModerationEnabled, self::MaintenanceMode, self::AdminRegistrationEnabled, self::AdminAccessRequestEnabled, self::FrontRegistrationEnabled, self::EcommerceAdminEnabled, self::EcommerceFrontEnabled, self::CrmAdminEnabled, self::ErpAdminEnabled, self::PhotoAdminEnabled, self::PhotoFrontEnabled, self::BillingAdminEnabled, self::GedAdminEnabled => 'bool',
            self::BillingInvoicePrefix, self::BillingCreditNotePrefix, self::EcommerceOrderPrefix, self::EcommerceListingPrefix, self::ErpProductPrefix, self::CrmDealPrefix, self::CrmContactPrefix, self::CrmCompanyPrefix => 'string',
            self::LogoMediaId, self::FaviconMediaId => 'media',
            default => 'string',
        };
    }

    /**
     * Single source of truth for the module dependency graph:
     *
     *   CRM ◀── ERP ◀── E-Commerce
     *
     * CRM is the foundational "parties" layer (Contacts, Companies). ERP
     * consumes it (Suppliers ≈ Company, future Invoicing ≈ Contact). E-Commerce
     * consumes ERP (Listing → Product) and CRM (future Customers ≈ Contact).
     *
     * Returns the parent parameter that must be on for this one to be enabled.
     * The reverse direction (cascade-on-disable) is derived from this.
     */
    public function getCascadeRequires(): ?string
    {
        return match ($this) {
            self::ErpAdminEnabled => self::CrmAdminEnabled->value,
            self::EcommerceAdminEnabled, self::EcommerceFrontEnabled => self::ErpAdminEnabled->value,
            self::BillingAdminEnabled => self::CrmAdminEnabled->value,
            default => null,
        };
    }

    /**
     * All descendant parameter keys (direct + transitive) that must be forced
     * to '0' when this parameter is turned off. Derived from getCascadeRequires
     * so the graph stays defined in a single place.
     *
     * @return list<string>
     */
    public function getCascadeDisableTargets(): array
    {
        $targets = [];
        foreach (self::cases() as $case) {
            if ($case->getCascadeRequires() === $this->value) {
                $targets[] = $case->value;
                foreach ($case->getCascadeDisableTargets() as $transitive) {
                    $targets[] = $transitive;
                }
            }
        }

        return array_values(array_unique($targets));
    }

    public function isAdminAccessible(): bool
    {
        return match ($this->getGroup()) {
            'general', 'reading', 'localization', 'branding', 'seo', 'system', 'ecommerce', 'email', 'sequences' => true,
            default => false,
        };
    }

    public function getModuleId(): ?string
    {
        return match ($this) {
            self::CrmAdminEnabled => 'crm',
            self::ErpAdminEnabled => 'erp',
            self::EcommerceAdminEnabled => 'ecommerce',
            self::PhotoAdminEnabled => 'photo',
            self::BillingAdminEnabled => 'billing',
            self::GedAdminEnabled => 'ged',
            default => null,
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
            self::CrmAdminEnabled, self::ErpAdminEnabled, self::EcommerceAdminEnabled, self::EcommerceFrontEnabled, self::PhotoAdminEnabled, self::PhotoFrontEnabled, self::BillingAdminEnabled, self::GedAdminEnabled => 'modules',
            self::BillingInvoicePrefix, self::BillingCreditNotePrefix, self::EcommerceOrderPrefix, self::EcommerceListingPrefix, self::ErpProductPrefix, self::CrmDealPrefix, self::CrmContactPrefix, self::CrmCompanyPrefix, self::PhotoGalleryPrefix, self::EditorialPostPrefix, self::EditorialFormPrefix, self::BillingTiersPrefix, self::CoreUserPrefix, self::CoreMediaPrefix, self::CoreAccessRequestPrefix, self::EditorialFormSubmissionPrefix, self::PhotoGalleryItemPrefix, self::PhotoGalleryInvitePrefix, self::EditorialCommentPrefix, self::CoreAuditLogPrefix, self::CoreResetPasswordPrefix, self::CoreMediaFolderPrefix, self::CoreMenuItemPrefix, self::BillingOcrJobPrefix, self::EcommerceCartPrefix, self::EcommerceCartItemPrefix, self::EcommerceOrderLinePrefix, self::EditorialFormFieldPrefix, self::EditorialTaxonomyTermPrefix, self::PhotoGalleryFinalizationPrefix, self::PhotoGalleryItemCommentPrefix, self::PhotoGalleryPickPrefix, self::GedDocumentPrefix => 'sequences',
            self::EcommerceLowStockThreshold => 'ecommerce',
            self::EmailLocale => 'email',
        };
    }
}
