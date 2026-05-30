<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-billing package. Loaded by
 * AuroraBillingBundle::loadExtension when the module is a standalone package.
 */

use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Module\Billing\Ocr\Service\DocTrClient;
use Aurora\Module\Billing\Ocr\Service\OllamaVisionClient;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Aurora\Module\Ged\Document\Contract\DocumentUsageProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $moduleDir = \dirname(__DIR__);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ModuleInterface::class)->tag('aurora.module');
    $services->instanceof(ConfigurationTabProviderInterface::class)->tag('aurora.configuration_tab_provider');
    $services->instanceof(ApplicationParameterProviderInterface::class)->tag('aurora.application_parameter_provider');
    $services->instanceof(DashboardStatsProviderInterface::class)->tag('aurora.dashboard_stats_provider');
    $services->instanceof(DocumentUsageProviderInterface::class)->tag('aurora.document_usage_provider');

    $services->load('Aurora\\Module\\Billing\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraBillingBundle.php',
            $moduleDir.'/{config,templates,translations,assets}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/BillingModuleParameterEnum.php',
        ]);

    // OCR clients with env-driven args (were central defs).
    $services->set(DocTrClient::class)
        ->arg('$baseUrl', '%env(OCR_DOCTR_URL)%')
        ->arg('$timeout', '%env(int:OCR_HTTP_TIMEOUT)%');

    $services->set(OllamaVisionClient::class)
        ->arg('$baseUrl', '%env(OLLAMA_URL)%')
        ->arg('$model', '%env(OLLAMA_VISION_MODEL)%')
        ->arg('$timeout', '%env(int:OCR_HTTP_TIMEOUT)%')
        ->arg('$numCtx', '%env(int:OCR_NUM_CTX)%')
        ->arg('$numPredict', '%env(int:OCR_NUM_PREDICT)%');
};
