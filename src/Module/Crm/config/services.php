<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-crm package. When the module is a
 * standalone Composer package, aurora-core's central `Aurora\: resource` does
 * not cover Crm, so the module registers its own services + the tags for the
 * core interfaces it implements. Loaded by AuroraCrmBundle::loadExtension.
 */

use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Reference\EntityReferenceProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $moduleDir = \dirname(__DIR__);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ModuleInterface::class)->tag('aurora.module');
    $services->instanceof(ConfigurationTabProviderInterface::class)->tag('aurora.configuration_tab_provider');
    $services->instanceof(ApplicationParameterProviderInterface::class)->tag('aurora.application_parameter_provider');
    $services->instanceof(DashboardStatsProviderInterface::class)->tag('aurora.dashboard_stats_provider');
    $services->instanceof(EntityReferenceProviderInterface::class)->tag('aurora.entity_reference_provider');

    $services->load('Aurora\\Module\\Crm\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraCrmBundle.php',
            $moduleDir.'/{config,templates,translations,assets}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/CrmModuleParameterEnum.php',
        ]);
};
