<?php

declare(strict_types=1);

namespace Aurora;

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
                    'Aurora\Core\Agency\Entity\AgencyInterface' => 'Aurora\Core\Agency\Entity\Agency',
                    'Aurora\Core\User\Entity\CoreUserInterface' => 'Aurora\Core\User\Entity\User',
                    'Aurora\Core\Audit\Entity\AuditLogInterface' => 'Aurora\Core\Audit\Entity\AuditLog',
                    'Aurora\Core\Auth\Entity\AccessRequestInterface' => 'Aurora\Core\Auth\Entity\AccessRequest',
                    'Aurora\Core\Auth\Entity\ResetPasswordRequestInterface' => 'Aurora\Core\Auth\Entity\ResetPasswordRequest',
                    'Aurora\Core\Locale\Entity\LocaleInterface' => 'Aurora\Core\Locale\Entity\Locale',
                    'Aurora\Core\Media\Entity\MediaInterface' => 'Aurora\Core\Media\Entity\Media',
                    'Aurora\Core\Media\Entity\MediaFolderInterface' => 'Aurora\Core\Media\Entity\MediaFolder',
                    'Aurora\Core\Menu\Entity\MenuInterface' => 'Aurora\Core\Menu\Entity\Menu',
                    'Aurora\Core\Menu\Entity\MenuItemInterface' => 'Aurora\Core\Menu\Entity\MenuItem',
                    'Aurora\Core\Menu\Entity\MenuItemTranslationInterface' => 'Aurora\Core\Menu\Entity\MenuItemTranslation',
                    'Aurora\Core\Notification\Entity\NotificationInterface' => 'Aurora\Core\Notification\Entity\Notification',
                    'Aurora\Core\Service\Entity\ServiceInterface' => 'Aurora\Core\Service\Entity\Service',
                    'Aurora\Core\Setting\Entity\SettingInterface' => 'Aurora\Core\Setting\Entity\Setting',
                    'Aurora\Core\Theme\Entity\ThemeInterface' => 'Aurora\Core\Theme\Entity\Theme',
                    'Aurora\Module\Billing\Invoice\Entity\InvoiceInterface' => 'Aurora\Module\Billing\Invoice\Entity\Invoice',
                    'Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface' => 'Aurora\Module\Billing\Invoice\Entity\InvoiceLine',
                    'Aurora\Module\Billing\Invoice\Entity\TiersInterface' => 'Aurora\Module\Billing\Invoice\Entity\Tiers',
                    'Aurora\Module\Billing\Ocr\Entity\OcrJobInterface' => 'Aurora\Module\Billing\Ocr\Entity\OcrJob',
                    'Aurora\Module\Crm\Company\Entity\CompanyInterface' => 'Aurora\Module\Crm\Company\Entity\Company',
                    'Aurora\Module\Crm\Contact\Entity\ContactInterface' => 'Aurora\Module\Crm\Contact\Entity\Contact',
                    'Aurora\Module\Editorial\Comment\Entity\CommentInterface' => 'Aurora\Module\Editorial\Comment\Entity\Comment',
                    'Aurora\Module\Editorial\Comment\Entity\CommentReactionInterface' => 'Aurora\Module\Editorial\Comment\Entity\CommentReaction',
                    'Aurora\Module\Editorial\Form\Entity\FormInterface' => 'Aurora\Module\Editorial\Form\Entity\Form',
                    'Aurora\Module\Editorial\Form\Entity\FormFieldInterface' => 'Aurora\Module\Editorial\Form\Entity\FormField',
                    'Aurora\Module\Editorial\Form\Entity\FormFieldTranslationInterface' => 'Aurora\Module\Editorial\Form\Entity\FormFieldTranslation',
                    'Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface' => 'Aurora\Module\Editorial\Form\Entity\FormSubmission',
                    'Aurora\Module\Editorial\Form\Entity\FormTranslationInterface' => 'Aurora\Module\Editorial\Form\Entity\FormTranslation',
                    'Aurora\Module\Editorial\Post\Entity\PostInterface' => 'Aurora\Module\Editorial\Post\Entity\Post',
                    'Aurora\Module\Editorial\Post\Entity\PostRevisionInterface' => 'Aurora\Module\Editorial\Post\Entity\PostRevision',
                    'Aurora\Module\Editorial\Post\Entity\PostSlugHistoryInterface' => 'Aurora\Module\Editorial\Post\Entity\PostSlugHistory',
                    'Aurora\Module\Editorial\Post\Entity\PostTranslationInterface' => 'Aurora\Module\Editorial\Post\Entity\PostTranslation',
                    'Aurora\Module\Editorial\Post\Entity\PostTypeInterface' => 'Aurora\Module\Editorial\Post\Entity\PostType',
                    'Aurora\Module\Editorial\Post\Entity\PostTypeFieldInterface' => 'Aurora\Module\Editorial\Post\Entity\PostTypeField',
                    'Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface' => 'Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy',
                    'Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface' => 'Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm',
                    'Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslationInterface' => 'Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation',
                    'Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslationInterface' => 'Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslation',
                    'Aurora\Module\Photo\Gallery\Entity\GalleryInterface' => 'Aurora\Module\Photo\Gallery\Entity\Gallery',
                    'Aurora\Module\Photo\Gallery\Entity\GalleryFinalizationInterface' => 'Aurora\Module\Photo\Gallery\Entity\GalleryFinalization',
                    'Aurora\Module\Photo\Gallery\Entity\GalleryInviteInterface' => 'Aurora\Module\Photo\Gallery\Entity\GalleryInvite',
                    'Aurora\Module\Photo\Gallery\Entity\GalleryItemInterface' => 'Aurora\Module\Photo\Gallery\Entity\GalleryItem',
                    'Aurora\Module\Photo\Gallery\Entity\GalleryItemCommentInterface' => 'Aurora\Module\Photo\Gallery\Entity\GalleryItemComment',
                    'Aurora\Module\Photo\Gallery\Entity\GalleryPickInterface' => 'Aurora\Module\Photo\Gallery\Entity\GalleryPick',
                    'Aurora\Module\Project\Entity\ProjectInterface' => 'Aurora\Module\Project\Entity\Project',
                    'Aurora\Module\Project\Entity\ProjectColumnInterface' => 'Aurora\Module\Project\Entity\ProjectColumn',
                    'Aurora\Module\Project\Entity\ProjectLabelInterface' => 'Aurora\Module\Project\Entity\ProjectLabel',
                    'Aurora\Module\Project\Entity\ProjectSavedViewInterface' => 'Aurora\Module\Project\Entity\ProjectSavedView',
                    'Aurora\Module\Project\Entity\ProjectSprintInterface' => 'Aurora\Module\Project\Entity\ProjectSprint',
                    'Aurora\Module\Project\Entity\ProjectTaskInterface' => 'Aurora\Module\Project\Entity\ProjectTask',
                    'Aurora\Module\Project\Entity\ProjectTaskCommentInterface' => 'Aurora\Module\Project\Entity\ProjectTaskComment',
                    'Aurora\Module\Project\Entity\ProjectTaskItemInterface' => 'Aurora\Module\Project\Entity\ProjectTaskItem',
                    'Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface' => 'Aurora\Module\Project\Entity\ProjectTaskTimeEntry',
                    'Aurora\Module\Crm\Deal\Entity\DealInterface' => 'Aurora\Module\Crm\Deal\Entity\Deal',
                    'Aurora\Module\Ecommerce\Cart\Entity\CartInterface' => 'Aurora\Module\Ecommerce\Cart\Entity\Cart',
                    'Aurora\Module\Ecommerce\Cart\Entity\CartItemInterface' => 'Aurora\Module\Ecommerce\Cart\Entity\CartItem',
                    'Aurora\Module\Ecommerce\Listing\Entity\ListingInterface' => 'Aurora\Module\Ecommerce\Listing\Entity\Listing',
                    'Aurora\Module\Ecommerce\Order\Entity\OrderInterface' => 'Aurora\Module\Ecommerce\Order\Entity\Order',
                    'Aurora\Module\Ecommerce\Order\Entity\OrderLineInterface' => 'Aurora\Module\Ecommerce\Order\Entity\OrderLine',
                    'Aurora\Module\Erp\Product\Entity\ProductInterface' => 'Aurora\Module\Erp\Product\Entity\Product',
                    'Aurora\Module\Ged\Document\Entity\DocumentInterface' => 'Aurora\Module\Ged\Document\Entity\Document',
                    'Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface' => 'Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory',
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
                ],
            ],
        ]);

        $builder->prependExtensionConfig('twig', [
            'file_name_pattern' => '*.twig',
            'paths' => [
                $dir.'/templates' => null,
                $dir.'/assets/styles' => 'styles',
                $dir.'/templates/Core' => 'Core',
                $dir.'/templates/Module/Editorial' => 'Editorial',
                $dir.'/templates/Shared' => 'Shared',
                $dir.'/templates/Module/Crm' => 'Crm',
                $dir.'/templates/Module/Erp' => 'Erp',
                $dir.'/templates/Module/Ecommerce' => 'Ecommerce',
                $dir.'/templates/Module/Photo' => 'Photo',
                $dir.'/templates/Module/Billing' => 'Billing',
                $dir.'/templates/Module/Ged' => 'Ged',
                $dir.'/templates/Module/Project' => 'Project',
            ],
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
                ],
                'fallbacks' => ['fr'],
            ],
        ]);
    }
}
