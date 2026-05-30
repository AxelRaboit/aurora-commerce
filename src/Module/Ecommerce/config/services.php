<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-commerce package — the MERGE case.
 * Ecommerce and Erp are inseparable (Ecommerce controllers autowire Erp's
 * concrete ProductRepository), so they form a single package. This one file
 * loads BOTH namespaces, which keeps cross-module autowiring intra-file (a
 * split services.php per module would hit "type excluded" on ProductRepository).
 * Loaded by AuroraEcommerceBundle::loadExtension; AuroraErpBundle ships no
 * services.php (no-op).
 */

use Aurora\Core\Content\BlockRendererInterface;
use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Aurora\Module\Ecommerce\Payment\StripeService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $ecommerceDir = \dirname(__DIR__);
    $erpDir = \dirname($ecommerceDir).'/Erp';

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    // Union of the tags both modules' services implement (file-scoped, applies
    // to everything loaded below; no-op for services that don't implement them).
    $services->instanceof(ModuleInterface::class)->tag('aurora.module');
    $services->instanceof(FrontendInterface::class)->tag('aurora.front');
    $services->instanceof(ConfigurationTabProviderInterface::class)->tag('aurora.configuration_tab_provider');
    $services->instanceof(ApplicationParameterProviderInterface::class)->tag('aurora.application_parameter_provider');
    $services->instanceof(DashboardStatsProviderInterface::class)->tag('aurora.dashboard_stats_provider');
    $services->instanceof(BlockRendererInterface::class)->tag('aurora.content_block_renderer');

    $services->load('Aurora\\Module\\Ecommerce\\', $ecommerceDir.'/')
        ->exclude([
            $ecommerceDir.'/AuroraEcommerceBundle.php',
            $ecommerceDir.'/{config,templates,translations,assets}',
            $ecommerceDir.'/**/Entity',
            $ecommerceDir.'/Setting/EcommerceModuleParameterEnum.php',
        ]);

    $services->load('Aurora\\Module\\Erp\\', $erpDir.'/')
        ->exclude([
            $erpDir.'/AuroraErpBundle.php',
            $erpDir.'/{templates,translations,assets}',
            $erpDir.'/**/Entity',
            $erpDir.'/Setting/ErpModuleParameterEnum.php',
        ]);

    // Stripe with env-driven args (was a central def).
    $services->set(StripeService::class)
        ->arg('$secretKey', '%env(STRIPE_SECRET_KEY)%')
        ->arg('$publicKey', '%env(STRIPE_PUBLIC_KEY)%')
        ->arg('$webhookSecret', '%env(STRIPE_WEBHOOK_SECRET)%');
};
