<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-personal-finance package. Loaded by
 * AuroraPersonalFinanceBundle::loadExtension when the module is standalone.
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

    $services->load('Aurora\\Module\\PersonalFinance\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraPersonalFinanceBundle.php',
            $moduleDir.'/{config,templates,translations,assets}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/PersonalFinanceModuleParameterEnum.php',
        ]);
};
