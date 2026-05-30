<?php

declare(strict_types=1);

namespace Aurora;

use Aurora\Core\Encryption\Doctrine\EncryptedStringType;
use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Locale\Entity\Locale;
use Aurora\Core\Locale\Entity\LocaleInterface;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Notification\Entity\Notification;
use Aurora\Core\Notification\Entity\NotificationInterface;
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
use Aurora\Module\Configuration\Setting\Entity\Setting;
use Aurora\Module\Configuration\Setting\Entity\SettingInterface;
use Aurora\Module\Configuration\Theme\Entity\Theme;
use Aurora\Module\Configuration\Theme\Entity\ThemeInterface;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Dev\Audit\Entity\AuditLog;
use Aurora\Module\Dev\Audit\Entity\AuditLogInterface;
use Aurora\Module\Dev\MountPoint\Entity\MountPoint;
use Aurora\Module\Dev\MountPoint\Entity\MountPointInterface;
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
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Entity\MenuInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuItem;
use Aurora\Module\Editorial\Menu\Entity\MenuItemInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuItemTranslation;
use Aurora\Module\Editorial\Menu\Entity\MenuItemTranslationInterface;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Entity\PostRevisionInterface;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistory;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistoryInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostTranslationInterface;
use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\PostType\Entity\PostTypeField;
use Aurora\Module\Editorial\PostType\Entity\PostTypeFieldInterface;
use Aurora\Module\Editorial\PostType\Entity\PostTypeInterface;
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
use Aurora\Module\Notes\PostIt\Entity\PostItNote;
use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPreset;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItemInterface;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRule;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategory;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoal;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWallet;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitation;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMember;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
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
use Aurora\Module\Platform\Agency\Entity\Agency;
use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Platform\Auth\Entity\AccessRequest;
use Aurora\Module\Platform\Auth\Entity\AccessRequestInterface;
use Aurora\Module\Platform\Auth\Entity\ResetPasswordRequest;
use Aurora\Module\Platform\Auth\Entity\ResetPasswordRequestInterface;
use Aurora\Module\Platform\Service\Entity\Service;
use Aurora\Module\Platform\Service\Entity\ServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
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
use Aurora\Module\Tools\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Tools\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Tools\Vault\VaultFolder\Entity\VaultFolder;
use Aurora\Module\Tools\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfig;
use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
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
                    VaultEntryInterface::class => VaultEntry::class,
                    VaultFolderInterface::class => VaultFolder::class,
                    VaultUserConfigInterface::class => VaultUserConfig::class,
                    MountPointInterface::class => MountPoint::class,
                    MarkdownNoteInterface::class => MarkdownNote::class,
                    BlockNoteInterface::class => BlockNote::class,
                    PostItNoteInterface::class => PostItNote::class,
                    ConversationInterface::class => Conversation::class,
                    MessageInterface::class => Message::class,
                    AssistantMountPointInterface::class => AssistantMountPoint::class,
                    PersonalFinanceWalletInterface::class => PersonalFinanceWallet::class,
                    PersonalFinanceWalletMemberInterface::class => PersonalFinanceWalletMember::class,
                    PersonalFinanceWalletInvitationInterface::class => PersonalFinanceWalletInvitation::class,
                    PersonalFinanceCategoryInterface::class => PersonalFinanceCategory::class,
                    PersonalFinanceTransactionInterface::class => PersonalFinanceTransaction::class,
                    PersonalFinanceBudgetInterface::class => PersonalFinanceBudget::class,
                    PersonalFinanceBudgetItemInterface::class => PersonalFinanceBudgetItem::class,
                    PersonalFinanceBudgetPresetInterface::class => PersonalFinanceBudgetPreset::class,
                    PersonalFinanceBudgetPresetItemInterface::class => PersonalFinanceBudgetPresetItem::class,
                    PersonalFinanceGoalInterface::class => PersonalFinanceGoal::class,
                    PersonalFinanceRecurringTransactionInterface::class => PersonalFinanceRecurringTransaction::class,
                    PersonalFinanceScheduledTransactionInterface::class => PersonalFinanceScheduledTransaction::class,
                    PersonalFinanceCategorizationRuleInterface::class => PersonalFinanceCategorizationRule::class,
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

        // Client templates take priority over Aurora's. For each Aurora namespace
        // we prepend the client-side path(s) first; the bundle path is registered
        // last as the fallback. Client overrides are recognized in two locations
        // for each namespace — the new co-located path (mirroring core's layout
        // since templates were moved under src/) AND the legacy top-level path
        // (kept for backward compat with existing client projects).
        $projectDir = (string) $builder->getParameter('kernel.project_dir');

        $twigPaths = [];

        // 1. Client-side overrides (highest priority — registered first).
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);
            $clientColocated = $projectDir.'/src/Module/'.$moduleName.'/templates';
            $clientLegacy = $projectDir.'/templates/Module/'.$moduleName;
            // Don't double-register when $projectDir === $dir (aurora-core dev mode).
            if ($clientColocated !== $dir.'/src/Module/'.$moduleName.'/templates' && is_dir($clientColocated)) {
                $twigPaths[$clientColocated] = $moduleName;
            }

            if (is_dir($clientLegacy)) {
                $twigPaths[$clientLegacy] = $moduleName;
            }
        }

        if ($projectDir !== $dir) {
            foreach (['Core', 'Shared'] as $namespace) {
                $clientColocated = $projectDir.'/src/Core/templates/'.$namespace;
                $clientLegacy = $projectDir.'/templates/'.$namespace;
                if (is_dir($clientColocated)) {
                    $twigPaths[$clientColocated] = $namespace;
                }

                if (is_dir($clientLegacy)) {
                    $twigPaths[$clientLegacy] = $namespace;
                }
            }
        }

        // 2. Bundle defaults (lowest priority — registered last).
        // Null namespace covers both the bundle's src/Core/templates/ (so
        // relative refs like 'Frontend/themes/default/...' still resolve) and
        // the legacy <bundle>/templates/ (still hosts templates/bundles/TwigBundle/
        // for Symfony's third-party override convention).
        $twigPaths[$dir.'/src/Core/templates'] = null;
        $twigPaths[$dir.'/templates'] = null;
        $twigPaths[$dir.'/src/Core/assets/css'] = 'styles';
        foreach (['Core', 'Shared'] as $namespace) {
            $bundleColocated = $dir.'/src/Core/templates/'.$namespace;
            if (is_dir($bundleColocated)) {
                $twigPaths[$bundleColocated] = $namespace;
            }
        }

        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);
            $bundleModuleTemplates = $moduleDir.'/templates';
            if (is_dir($bundleModuleTemplates)) {
                $twigPaths[$bundleModuleTemplates] = $moduleName;
            }
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
                        glob($dir.'/src/Module/*/*/translations', GLOB_ONLYDIR) ?: [],
                        $coreDirs,
                    ),
                    is_dir(...),
                )),
                'fallbacks' => [LocaleEnum::default()->value],
            ],
        ]);
    }
}
