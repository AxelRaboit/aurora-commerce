<?php

declare(strict_types=1);

namespace App\Enum\ApplicationParameter;

enum VeloxApplicationParameterEnum: string implements ApplicationParameterEnumInterface
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
    case MaintenanceMode = 'maintenance_mode';
    case RegistrationEnabled = 'registration_enabled';
    case PostRevisionsLimit = 'post_revisions_limit';

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
            self::MaintenanceMode => 'Mode maintenance',
            self::RegistrationEnabled => 'Inscriptions ouvertes',
            self::PostRevisionsLimit => 'Nombre de révisions gardées par article',
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
            self::MaintenanceMode => 'Mode maintenance (0 = désactivé, 1 = site fermé au public)',
            self::RegistrationEnabled => 'Autoriser les nouvelles inscriptions (0 = désactivé, 1 = activé)',
            self::PostRevisionsLimit => 'Nombre maximal de révisions conservées par article (les plus anciennes sont supprimées)',
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
            self::MaintenanceMode => '0',
            self::RegistrationEnabled => '0',
            self::PostRevisionsLimit => '20',
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::PostsPerPage, self::MaxUploadSizeMb, self::PostRevisionsLimit => 'int',
            self::CommentsEnabled, self::MaintenanceMode, self::RegistrationEnabled => 'bool',
            default => 'string',
        };
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::SiteName, self::SiteDescription, self::SiteUrl, self::AdminEmail => 'general',
            self::DefaultLocale, self::Timezone, self::DateFormat => 'localization',
            self::PostsPerPage, self::CommentsEnabled, self::PostRevisionsLimit => 'reading',
            self::MaxUploadSizeMb, self::AllowedUploadExtensions => 'media',
            self::MaintenanceMode, self::RegistrationEnabled => 'system',
        };
    }
}
