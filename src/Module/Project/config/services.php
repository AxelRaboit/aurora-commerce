<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-project package. Loaded by
 * AuroraProjectBundle::loadExtension when the module is a standalone package.
 */

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Search\BackendSearchProviderInterface;
use Aurora\Core\Search\SearchProviderInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Aurora\Module\Ged\Document\Contract\DocumentUsageProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $moduleDir = \dirname(__DIR__);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ModuleInterface::class)->tag('aurora.module');
    $services->instanceof(ApplicationParameterProviderInterface::class)->tag('aurora.application_parameter_provider');
    $services->instanceof(SearchProviderInterface::class)->tag('aurora.search_provider');
    $services->instanceof(BackendSearchProviderInterface::class)->tag('aurora.backend_search_provider');
    $services->instanceof(DocumentUsageProviderInterface::class)->tag('aurora.document_usage_provider');

    $services->load('Aurora\\Module\\Project\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraProjectBundle.php',
            $moduleDir.'/{config,templates,translations,assets,DataFixtures}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/ProjectModuleParameterEnum.php',
        ]);
};
