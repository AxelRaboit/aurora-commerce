<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Enum;

enum ApplicationParameterEnum: string implements ApplicationParameterEnumInterface
{
    case SiteName = 'site_name';
    case SiteDescription = 'site_description';
    case SiteUrl = 'site_url';
    case AdminEmail = 'admin_email';
    case DefaultLocale = 'default_locale';
    case PostsPerPage = 'posts_per_page';
    case MaxUploadSizeMb = 'max_upload_size_mb';
    case AllowedUploadExtensions = 'allowed_upload_extensions';
    case Timezone = 'timezone';
    case DateFormat = 'date_format';
    case CommentsEnabled = 'comments_enabled';
    case CommentModerationEnabled = 'comment_moderation_enabled';
    case MaintenanceMode = 'maintenance_mode';
    case AdminRegistrationEnabled = 'admin_registration_enabled';
    case AdminAccessRequestEnabled = 'admin_access_request_enabled';
    case FrontRegistrationEnabled = 'front_registration_enabled';
    case PostRevisionsLimit = 'post_revisions_limit';
    case TrashAutoPurgeDays = 'trash_auto_purge_days';
    case HomepagePostId = 'homepage_post_id';
    case LogoMediaId = 'logo_media_id';
    case FaviconMediaId = 'favicon_media_id';
    case SeoTitleTemplate = 'seo_title_template';
    case SeoDefaultDescription = 'seo_default_description';
    case EcommerceAdminEnabled = 'ecommerce_admin_enabled';
    case EcommerceFrontEnabled = 'ecommerce_front_enabled';
    case EcommerceLowStockThreshold = 'ecommerce_low_stock_threshold';
    case CrmAdminEnabled = 'crm_admin_enabled';
    case ErpAdminEnabled = 'erp_admin_enabled';
    case PhotoAdminEnabled = 'photo_admin_enabled';
    case PhotoFrontEnabled = 'photo_front_enabled';
    case BillingAdminEnabled = 'billing_admin_enabled';
    case BillingInvoicePrefix = 'billing_invoice_prefix';
    case EmailLocale = 'email_locale';

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
            self::BillingInvoicePrefix => 'Préfixe des numéros de factures reçues',
            self::EmailLocale => 'Langue forcée pour les emails',
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
            self::BillingInvoicePrefix => 'Préfixe du numéro auto-généré à la validation (ex: FO). Laissez vide pour désactiver.',
            self::BillingAdminEnabled => "Active la section Facturation dans l'administration (factures fournisseurs, OCR, import). Décocher cache la sidebar et 404 les routes /admin/billing/*.",
            self::EmailLocale => 'Code langue (fr, en, es, de) à forcer pour tous les emails sortants. Vide = utiliser la langue de la requête courante.',
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
            self::CommentsEnabled => '0',
            self::CommentModerationEnabled => '1',
            self::MaintenanceMode => '0',
            self::AdminRegistrationEnabled => '0',
            self::AdminAccessRequestEnabled => '1',
            self::FrontRegistrationEnabled => '0',
            self::PostRevisionsLimit => '20',
            self::TrashAutoPurgeDays => '30',
            self::HomepagePostId => '',
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
            self::BillingInvoicePrefix => 'FO',
            self::EmailLocale => '',
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::PostsPerPage, self::MaxUploadSizeMb, self::PostRevisionsLimit, self::TrashAutoPurgeDays, self::EcommerceLowStockThreshold => 'int',
            self::HomepagePostId => 'post',
            self::CommentsEnabled, self::CommentModerationEnabled, self::MaintenanceMode, self::AdminRegistrationEnabled, self::AdminAccessRequestEnabled, self::FrontRegistrationEnabled, self::EcommerceAdminEnabled, self::EcommerceFrontEnabled, self::CrmAdminEnabled, self::ErpAdminEnabled, self::PhotoAdminEnabled, self::PhotoFrontEnabled, self::BillingAdminEnabled => 'bool',
            self::BillingInvoicePrefix => 'string',
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
            'general', 'reading', 'localization', 'branding', 'seo', 'system', 'ecommerce', 'email' => true,
            default => false,
        };
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::SiteName, self::SiteDescription, self::SiteUrl, self::AdminEmail => 'general',
            self::DefaultLocale, self::Timezone, self::DateFormat => 'localization',
            self::PostsPerPage, self::CommentsEnabled, self::CommentModerationEnabled, self::PostRevisionsLimit, self::TrashAutoPurgeDays, self::HomepagePostId => 'reading',
            self::MaxUploadSizeMb, self::AllowedUploadExtensions => 'media',
            self::MaintenanceMode, self::AdminRegistrationEnabled, self::AdminAccessRequestEnabled, self::FrontRegistrationEnabled => 'system',
            self::LogoMediaId, self::FaviconMediaId => 'branding',
            self::SeoTitleTemplate, self::SeoDefaultDescription => 'seo',
            self::CrmAdminEnabled, self::ErpAdminEnabled, self::EcommerceAdminEnabled, self::EcommerceFrontEnabled, self::PhotoAdminEnabled, self::PhotoFrontEnabled, self::BillingAdminEnabled, self::BillingInvoicePrefix => 'modules',
            self::EcommerceLowStockThreshold => 'ecommerce',
            self::EmailLocale => 'email',
        };
    }
}
