<?php

declare(strict_types=1);

namespace App\Enum\ApplicationParameter;

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
    case FrontRegistrationEnabled = 'front_registration_enabled';
    case PostRevisionsLimit = 'post_revisions_limit';
    case TrashAutoPurgeDays = 'trash_auto_purge_days';
    case HomepagePostId = 'homepage_post_id';
    case LogoMediaId = 'logo_media_id';
    case FaviconMediaId = 'favicon_media_id';
    case SeoTitleTemplate = 'seo_title_template';
    case SeoDefaultDescription = 'seo_default_description';

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
            self::FrontRegistrationEnabled => 'Inscriptions front ouvertes',
            self::PostRevisionsLimit => 'Nombre de révisions gardées par article',
            self::TrashAutoPurgeDays => 'Purge auto de la corbeille (jours)',
            self::HomepagePostId => "Page d'accueil (ID du post)",
            self::LogoMediaId => 'Logo du site',
            self::FaviconMediaId => 'Favicon',
            self::SeoTitleTemplate => 'Template de titre SEO',
            self::SeoDefaultDescription => 'Description SEO par défaut',
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
            self::FrontRegistrationEnabled => 'Autoriser les inscriptions publiques sur le front (0 = désactivé, 1 = activé)',
            self::PostRevisionsLimit => 'Nombre maximal de révisions conservées par article (les plus anciennes sont supprimées)',
            self::TrashAutoPurgeDays => 'Nombre de jours avant suppression définitive des articles en corbeille (0 = jamais)',
            self::HomepagePostId => 'ID d\'un post affiché sur la page d\'accueil. Vide = liste des derniers articles.',
            self::LogoMediaId => 'ID du média utilisé comme logo',
            self::FaviconMediaId => 'ID du média utilisé comme favicon',
            self::SeoTitleTemplate => 'Template pour le titre des pages. Utilisez {title} et {siteName}. Ex: {title} — {siteName}',
            self::SeoDefaultDescription => 'Meta description utilisée quand aucune description spécifique n\'est définie',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::SiteName => 'Mon site Velox',
            self::SiteDescription => 'Propulsé par Velox CMS',
            self::SiteUrl => 'http://localhost',
            self::AdminEmail => 'admin@velox.app',
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
            self::FrontRegistrationEnabled => '0',
            self::PostRevisionsLimit => '20',
            self::TrashAutoPurgeDays => '30',
            self::HomepagePostId => '',
            self::LogoMediaId => '',
            self::FaviconMediaId => '',
            self::SeoTitleTemplate => '{title} — {siteName}',
            self::SeoDefaultDescription => '',
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::PostsPerPage, self::MaxUploadSizeMb, self::PostRevisionsLimit, self::TrashAutoPurgeDays => 'int',
            self::HomepagePostId => 'post',
            self::CommentsEnabled, self::CommentModerationEnabled, self::MaintenanceMode, self::AdminRegistrationEnabled, self::FrontRegistrationEnabled => 'bool',
            self::LogoMediaId, self::FaviconMediaId => 'media',
            default => 'string',
        };
    }

    public function isAdminAccessible(): bool
    {
        return match ($this->getGroup()) {
            'general', 'reading', 'localization', 'branding', 'seo', 'system' => true,
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
            self::MaintenanceMode, self::AdminRegistrationEnabled, self::FrontRegistrationEnabled => 'system',
            self::LogoMediaId, self::FaviconMediaId => 'branding',
            self::SeoTitleTemplate, self::SeoDefaultDescription => 'seo',
        };
    }
}
