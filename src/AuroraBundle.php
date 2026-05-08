<?php

declare(strict_types=1);

namespace Aurora;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Audit\Entity\AuditLog;
use Aurora\Core\Audit\Entity\AuditLogInterface;
use Aurora\Core\Auth\Entity\AccessRequest;
use Aurora\Core\Auth\Entity\AccessRequestInterface;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Auth\Entity\ResetPasswordRequestInterface;
use Aurora\Core\Locale\Entity\Locale;
use Aurora\Core\Locale\Entity\LocaleInterface;
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
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Cart\Entity\CartItemInterface;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
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
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
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
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AuroraBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(dirname(__DIR__).'/config/services.yaml');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $dir = dirname(__DIR__);

        $builder->prependExtensionConfig('doctrine', [
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
                    DealInterface::class => Deal::class,
                    CartInterface::class => Cart::class,
                    CartItemInterface::class => CartItem::class,
                    ListingInterface::class => Listing::class,
                    OrderInterface::class => Order::class,
                    OrderLineInterface::class => OrderLine::class,
                    ProductInterface::class => Product::class,
                    DocumentInterface::class => Document::class,
                    DocumentCategoryInterface::class => DocumentCategory::class,
                ],
                'mappings' => [
                    'AuroraCore' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Core',
                        'prefix' => 'Aurora\Core',
                        'alias' => 'AuroraCore',
                    ],
                    'AuroraEditorial' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Editorial',
                        'prefix' => 'Aurora\Module\Editorial',
                        'alias' => 'AuroraEditorial',
                    ],
                    'AuroraCrm' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Crm',
                        'prefix' => 'Aurora\Module\Crm',
                        'alias' => 'AuroraCrm',
                    ],
                    'AuroraErp' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Erp',
                        'prefix' => 'Aurora\Module\Erp',
                        'alias' => 'AuroraErp',
                    ],
                    'AuroraEcommerce' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Ecommerce',
                        'prefix' => 'Aurora\Module\Ecommerce',
                        'alias' => 'AuroraEcommerce',
                    ],
                    'AuroraPhoto' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Photo',
                        'prefix' => 'Aurora\Module\Photo',
                        'alias' => 'AuroraPhoto',
                    ],
                    'AuroraBilling' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Billing',
                        'prefix' => 'Aurora\Module\Billing',
                        'alias' => 'AuroraBilling',
                    ],
                    'AuroraGed' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Ged',
                        'prefix' => 'Aurora\Module\Ged',
                        'alias' => 'AuroraGed',
                    ],
                    'AuroraProject' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Project',
                        'prefix' => 'Aurora\Module\Project',
                        'alias' => 'AuroraProject',
                    ],
                    'AuroraPlanning' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $dir.'/src/Module/Planning',
                        'prefix' => 'Aurora\Module\Planning',
                        'alias' => 'AuroraPlanning',
                    ],
                ],
            ],
        ]);

        // Client templates take priority over Aurora's. For each Aurora namespace,
        // we prepend the client's templates/<Namespace> directory if it exists, so
        // clients can override any Aurora template by mirroring its path under
        // templates/Core, templates/Module/Editorial, templates/Shared, etc.
        $projectDir = (string) $builder->getParameter('kernel.project_dir');
        $namespacedPaths = [
            'Core' => '/templates/Core',
            'Editorial' => '/templates/Module/Editorial',
            'Shared' => '/templates/Shared',
            'Crm' => '/templates/Module/Crm',
            'Erp' => '/templates/Module/Erp',
            'Ecommerce' => '/templates/Module/Ecommerce',
            'Photo' => '/templates/Module/Photo',
            'Billing' => '/templates/Module/Billing',
            'Ged' => '/templates/Module/Ged',
            'Project' => '/templates/Module/Project',
            'Planning' => '/templates/Module/Planning',
        ];

        $twigPaths = [];
        foreach ($namespacedPaths as $namespace => $relative) {
            if (is_dir($projectDir.$relative)) {
                $twigPaths[$projectDir.$relative] = $namespace;
            }
        }

        $twigPaths[$dir.'/templates'] = null;
        $twigPaths[$dir.'/assets/styles'] = 'styles';
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

        $builder->prependExtensionConfig('framework', [
            'default_locale' => 'fr',
            'enabled_locales' => ['fr', 'en'],
            'translator' => [
                'default_path' => $dir.'/src/Core/translations',
                'paths' => [
                    $dir.'/src/Module/Editorial/translations',
                    $dir.'/src/Module/Crm/translations',
                    $dir.'/src/Module/Erp/translations',
                    $dir.'/src/Module/Ecommerce/translations',
                    $dir.'/src/Module/Photo/translations',
                    $dir.'/src/Module/Billing/translations',
                    $dir.'/src/Module/Ged/translations',
                    $dir.'/src/Module/Project/translations',
                    $dir.'/src/Module/Planning/translations',
                ],
                'fallbacks' => ['fr'],
            ],
        ]);
    }
}
