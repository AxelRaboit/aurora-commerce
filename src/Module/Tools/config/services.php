<?php

declare(strict_types=1);

/**
 * Services config shipped INSIDE the aurora-tools package. When the module is
 * a standalone Composer package, aurora-core's central `Aurora\: resource`
 * does not cover Tools, so the module registers its own services + the tags
 * for the core interfaces it implements. Loaded by AuroraToolsBundle's
 * loadExtension (via AbstractAuroraModuleBundle).
 */

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $moduleDir = \dirname(__DIR__);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    // Tag the core interfaces this module's services implement (these would be
    // covered by aurora-core's central _instanceof in the monorepo, but a
    // standalone package must declare them for its own services).
    $services->instanceof(ModuleInterface::class)->tag('aurora.module');
    $services->instanceof(ApplicationParameterProviderInterface::class)
        ->tag('aurora.application_parameter_provider');

    $services->load('Aurora\\Module\\Tools\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraToolsBundle.php',
            $moduleDir.'/{config,templates,translations,assets}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/ToolsModuleParameterEnum.php',
        ]);
};
