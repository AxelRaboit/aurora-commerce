<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-hr package. Loaded by
 * AuroraHrBundle::loadExtension when the module is a standalone package.
 */

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $moduleDir = \dirname(__DIR__);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ModuleInterface::class)->tag('aurora.module');
    $services->instanceof(ApplicationParameterProviderInterface::class)->tag('aurora.application_parameter_provider');

    $services->load('Aurora\\Module\\Hr\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraHrBundle.php',
            $moduleDir.'/{config,templates,translations,assets,DataFixtures}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/HrModuleParameterEnum.php',
        ]);
};
