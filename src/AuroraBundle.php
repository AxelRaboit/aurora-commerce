<?php

declare(strict_types=1);

namespace Aurora;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Dev\Audit\Entity\AuditLog;
use Aurora\Core\Dev\Audit\Entity\AuditLogInterface;
use Aurora\Core\Auth\Entity\AccessRequest;
use Aurora\Core\Auth\Entity\AccessRequestInterface;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Auth\Entity\ResetPasswordRequestInterface;
use Aurora\Core\Encryption\Doctrine\EncryptedStringType;
use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Locale\Entity\Locale;
use Aurora\Core\Locale\Entity\LocaleInterface;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Aurora\Core\Media\Entity\MediaFolderInterface;
use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuInterface;
use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Entity\MenuItemInterface;
use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Aurora\Core\Menu\Entity\MenuItemTranslationInterface;
use Aurora\Core\MountPoint\Entity\MountPoint;
use Aurora\Core\MountPoint\Entity\MountPointInterface;
use Aurora\Core\Notification\Entity\Notification;
use Aurora\Core\Notification\Entity\NotificationInterface;
use Aurora\Core\Service\Entity\Service;
use Aurora\Core\Service\Entity\ServiceInterface;
use Aurora\Core\Setting\Entity\Setting;
use Aurora\Core\Setting\Entity\SettingInterface;
use Aurora\Core\Theme\Entity\Theme;
use Aurora\Core\Theme\Entity\ThemeInterface;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Assistant\Conversation\Entity\Conversation;
use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;
use Aurora\Module\Assistant\Conversation\Entity\Message;
use Aurora\Module\Assistant\Conversation\Entity\MessageInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Cart\Entity\CartItemInterface;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslationInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslationInterface;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderInterface;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Ecommerce\Order\Entity\OrderLineInterface;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Entity\CommentReaction;
use Aurora\Module\Editorial\Comment\Entity\CommentReactionInterface;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormFieldInterface;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslationInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Entity\FormTranslationInterface;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Entity\PostRevisionInterface;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistory;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistoryInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostTranslationInterface;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Entity\PostTypeField;
use Aurora\Module\Editorial\Post\Entity\PostTypeFieldInterface;
use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslationInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslation;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslationInterface;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Entity\DocumentVersion;
use Aurora\Module\Ged\Document\Entity\DocumentVersionInterface;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolder;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTag;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Notes\Block\Entity\BlockNote;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocument;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateField;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryFinalization;
use Aurora\Module\Photo\Gallery\Entity\GalleryFinalizationInterface;
use Aurora\Module\Photo\Gallery\Entity\GalleryInterface;
use Aurora\Module\Photo\Gallery\Entity\GalleryInvite;
use Aurora\Module\Photo\Gallery\Entity\GalleryInviteInterface;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Entity\GalleryItemComment;
use Aurora\Module\Photo\Gallery\Entity\GalleryItemCommentInterface;
use Aurora\Module\Photo\Gallery\Entity\GalleryItemInterface;
use Aurora\Module\Photo\Gallery\Entity\GalleryPick;
use Aurora\Module\Photo\Gallery\Entity\GalleryPickInterface;
use Aurora\Module\Planning\Event\Entity\PlanningEvent;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectLabelInterface;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Aurora\Module\Project\Entity\ProjectSavedViewInterface;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Entity\ProjectSprintInterface;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use Aurora\Module\Project\Entity\ProjectTaskItemInterface;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfig;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AuroraBundle extends AbstractBundle
{
    /**
     * Override AbstractBundle::getPath() — the default returns
     * `dirname(file, 2)` which resolves to the project root (or vendor
     * package root when used by a client). That makes Symfony's
     * `assets:install` treat the project's `public/` as the bundle's
     * `Resources/public` and copy it recursively into
     * `public/bundles/aurora/` — infinite nesting.
     *
     * Returning `__DIR__` (the `src/` dir) scopes the bundle to its
     * code dir; no `src/public/` exists, so no asset copy happens.
     * All internal paths in this bundle use `dirname(__DIR__)` directly,
     * so the override doesn't affect translations / Doctrine mappings /
     * Twig namespaces — they still resolve against the project root.
     */
    #[Override]
    public function getPath(): string
    {
        return __DIR__;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(dirname(__DIR__).'/config/services.yaml');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $dir = dirname(__DIR__);

        $moduleDirs = glob($dir.'/src/Module/*', GLOB_ONLYDIR) ?: [];

        $builder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    EncryptedTextType::NAME => EncryptedTextType::class,
                    EncryptedStringType::NAME => EncryptedStringType::class,
                ],
            ],
            'orm' => [
                'validate_xml_mapping' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
                'identity_generation_preferences' => [
                    PostgreSQLPlatform::class => 'identity',
                ],
                'auto_mapping' => false,
                'resolve_target_entities' => [
                    AgencyInterface::class => Agency::class,
                    CoreUserInterface::class => User::class,
                    AuditLogInterface::class => AuditLog::class,
                    AccessRequestInterface::class => AccessRequest::class,
                    ResetPasswordRequestInterface::class => ResetPasswordRequest::class,
                    LocaleInterface::class => Locale::class,
                    MediaInterface::class => Media::class,
                    MediaFolderInterface::class => MediaFolder::class,
                    MenuInterface::class => Menu::class,
                    MenuItemInterface::class => MenuItem::class,
                    MenuItemTranslationInterface::class => MenuItemTranslation::class,
                    NotificationInterface::class => Notification::class,
                    ServiceInterface::class => Service::class,
                    SettingInterface::class => Setting::class,
                    ThemeInterface::class => Theme::class,
                    InvoiceInterface::class => Invoice::class,
                    InvoiceLineInterface::class => InvoiceLine::class,
                    TiersInterface::class => Tiers::class,
                    OcrJobInterface::class => OcrJob::class,
                    CompanyInterface::class => Company::class,
                    ContactInterface::class => Contact::class,
                    ContactTagInterface::class => ContactTag::class,
                    CommentInterface::class => Comment::class,
                    CommentReactionInterface::class => CommentReaction::class,
                    FormInterface::class => Form::class,
                    FormFieldInterface::class => FormField::class,
                    FormFieldTranslationInterface::class => FormFieldTranslation::class,
                    FormSubmissionInterface::class => FormSubmission::class,
                    FormTranslationInterface::class => FormTranslation::class,
                    PostInterface::class => Post::class,
                    PostRevisionInterface::class => PostRevision::class,
                    PostSlugHistoryInterface::class => PostSlugHistory::class,
                    PostTranslationInterface::class => PostTranslation::class,
                    PostTypeInterface::class => PostType::class,
                    PostTypeFieldInterface::class => PostTypeField::class,
                    TaxonomyInterface::class => Taxonomy::class,
                    TaxonomyTermInterface::class => TaxonomyTerm::class,
                    TaxonomyTermTranslationInterface::class => TaxonomyTermTranslation::class,
                    TaxonomyTranslationInterface::class => TaxonomyTranslation::class,
                    GalleryInterface::class => Gallery::class,
                    GalleryFinalizationInterface::class => GalleryFinalization::class,
                    GalleryInviteInterface::class => GalleryInvite::class,
                    GalleryItemInterface::class => GalleryItem::class,
                    GalleryItemCommentInterface::class => GalleryItemComment::class,
                    GalleryPickInterface::class => GalleryPick::class,
                    ProjectInterface::class => Project::class,
                    ProjectColumnInterface::class => ProjectColumn::class,
                    ProjectLabelInterface::class => ProjectLabel::class,
                    ProjectSavedViewInterface::class => ProjectSavedView::class,
                    ProjectSprintInterface::class => ProjectSprint::class,
                    ProjectTaskInterface::class => ProjectTask::class,
                    ProjectTaskCommentInterface::class => ProjectTaskComment::class,
                    ProjectTaskItemInterface::class => ProjectTaskItem::class,
                    ProjectTaskTimeEntryInterface::class => ProjectTaskTimeEntry::class,
                    PlanningInterface::class => Planning::class,
                    PlanningEventInterface::class => PlanningEvent::class,
                    EmployeeInterface::class => Employee::class,
                    DealInterface::class => Deal::class,
                    CartInterface::class => Cart::class,
                    CartItemInterface::class => CartItem::class,
                    ListingInterface::class => Listing::class,
                    ListingCategoryInterface::class => ListingCategory::class,
                    ListingCategoryTranslationInterface::class => ListingCategoryTranslation::class,
                    ListingTagInterface::class => ListingTag::class,
                    ListingTagTranslationInterface::class => ListingTagTranslation::class,
                    OrderInterface::class => Order::class,
                    OrderLineInterface::class => OrderLine::class,
                    ProductInterface::class => Product::class,
                    DocumentInterface::class => Document::class,
                    DocumentVersionInterface::class => DocumentVersion::class,
                    DocumentCategoryInterface::class => DocumentCategory::class,
                    DocumentTagInterface::class => DocumentTag::class,
                    DocumentFolderInterface::class => DocumentFolder::class,
                    PdfTemplateInterface::class => PdfTemplate::class,
                    PdfTemplateFieldInterface::class => PdfTemplateField::class,
                    PdfDocumentInterface::class => PdfDocument::class,
                    VaultEntryInterface::class => VaultEntry::class,
                    VaultFolderInterface::class => VaultFolder::class,
                    VaultUserConfigInterface::class => VaultUserConfig::class,
                    MountPointInterface::class => MountPoint::class,
                    MarkdownNoteInterface::class => MarkdownNote::class,
                    BlockNoteInterface::class => BlockNote::class,
                    ConversationInterface::class => Conversation::class,
                    MessageInterface::class => Message::class,
                    AssistantMountPointInterface::class => AssistantMountPoint::class,
                ],
                'mappings' => array_merge(
                    [
                        'AuroraCore' => [
                            'type' => 'attribute',
                            'is_bundle' => false,
                            'dir' => $dir.'/src/Core',
                            'prefix' => 'Aurora\Core',
                            'alias' => 'AuroraCore',
                        ],
                    ],
                    ...array_map(static function (string $moduleDir): array {
                        $moduleName = basename($moduleDir);

                        return [
                            'Aurora'.$moduleName => [
                                'type' => 'attribute',
                                'is_bundle' => false,
                                'dir' => $moduleDir,
                                'prefix' => 'Aurora\\Module\\'.$moduleName,
                                'alias' => 'Aurora'.$moduleName,
                            ],
                        ];
                    }, $moduleDirs),
                ),
            ],
        ]);

        // Client templates take priority over Aurora's. For each Aurora namespace,
        // we prepend the client's templates/<Namespace> directory if it exists, so
        // clients can override any Aurora template by mirroring its path under
        // templates/Core, templates/Module/Editorial, templates/Shared, etc.
        $projectDir = (string) $builder->getParameter('kernel.project_dir');
        $moduleNamespacedPaths = [];
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);
            $relative = '/templates/Module/'.$moduleName;
            if (is_dir($dir.$relative)) {
                $moduleNamespacedPaths[$moduleName] = $relative;
            }
        }

        $namespacedPaths = array_merge(
            ['Core' => '/templates/Core', 'Shared' => '/templates/Shared'],
            $moduleNamespacedPaths,
        );

        $twigPaths = [];
        foreach ($namespacedPaths as $namespace => $relative) {
            if (is_dir($projectDir.$relative)) {
                $twigPaths[$projectDir.$relative] = $namespace;
            }
        }

        $twigPaths[$dir.'/templates'] = null;
        $twigPaths[$dir.'/assets/css'] = 'styles';
        foreach ($namespacedPaths as $namespace => $relative) {
            $twigPaths[$dir.$relative] = $namespace;
        }

        $builder->prependExtensionConfig('twig', [
            'file_name_pattern' => '*.twig',
            'paths' => $twigPaths,
        ]);

        $builder->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => [
                'DoctrineMigrations' => $dir.'/migrations',
            ],
            'enable_profiler' => false,
        ]);

        $coreDirs = array_merge(
            glob($dir.'/src/Core/*/translations', GLOB_ONLYDIR) ?: [],
            glob($dir.'/src/Core/*/*/translations', GLOB_ONLYDIR) ?: [],
        );

        $builder->prependExtensionConfig('framework', [
            'default_locale' => LocaleEnum::default()->value,
            'enabled_locales' => LocaleEnum::values(),
            'translator' => [
                'default_path' => $dir.'/src/Core/translations',
                'paths' => array_values(array_filter(
                    array_merge(
                        array_map(static fn (string $moduleDir): string => $moduleDir.'/translations', $moduleDirs),
                        $coreDirs,
                    ),
                    is_dir(...),
                )),
                'fallbacks' => [LocaleEnum::default()->value],
            ],
        ]);
    }
}
