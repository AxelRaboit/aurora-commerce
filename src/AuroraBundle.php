<?php

declare(strict_types=1);

namespace Aurora;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AuroraBundle extends AbstractBundle
{
    #[Override]
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getPublicDir(): ?string
    {
        return null;
    }

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
                ],
            ],
        ]);

        $builder->prependExtensionConfig('twig', [
            'file_name_pattern' => '*.twig',
            'paths' => [
                $dir.'/assets/styles' => 'styles',
                $dir.'/templates/Core' => 'Core',
                $dir.'/templates/Module/Editorial' => 'Editorial',
                $dir.'/templates/Shared' => 'Shared',
                $dir.'/templates/Module/Crm' => 'Crm',
                $dir.'/templates/Module/Erp' => 'Erp',
                $dir.'/templates/Module/Ecommerce' => 'Ecommerce',
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
            'enabled_locales' => ['fr', 'en', 'es', 'de'],
            'translator' => [
                'default_path' => $dir.'/src/Core/translations',
                'paths' => [
                    $dir.'/src/Module/Editorial/translations',
                    $dir.'/src/Module/Crm/translations',
                    $dir.'/src/Module/Erp/translations',
                    $dir.'/src/Module/Ecommerce/translations',
                ],
                'fallbacks' => ['fr'],
            ],
        ]);
    }
}
