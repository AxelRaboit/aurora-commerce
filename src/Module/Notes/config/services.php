<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-notes package. Loaded by
 * AuroraNotesBundle::loadExtension when the module is a standalone package.
 */

use Aurora\Core\Module\Contract\ModuleInterface;
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

    $services->load('Aurora\\Module\\Notes\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraNotesBundle.php',
            $moduleDir.'/{config,templates,translations,assets,DataFixtures}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/NotesModuleParameterEnum.php',
        ]);
};
