<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-assistant package. Loaded by
 * AuroraAssistantBundle::loadExtension when the module is a standalone package.
 */

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Module\Assistant\Llm\Service\AnthropicChatClient;
use Aurora\Module\Assistant\Llm\Service\OllamaChatClient;
use Aurora\Module\Assistant\Setting\AssistantSettings;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;
use Aurora\Module\Assistant\Vision\Service\OllamaVisionDescriber;
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
    $services->instanceof(ToolInterface::class)->tag('aurora.assistant.tool');

    $services->load('Aurora\\Module\\Assistant\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraAssistantBundle.php',
            $moduleDir.'/{config,templates,translations,assets}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/AssistantModuleParameterEnum.php',
        ]);

    // LLM clients + settings with env-driven args (were central defs).
    $services->set(AssistantSettings::class)
        ->arg('$envChatModel', '%env(ASSISTANT_CHAT_MODEL)%')
        ->arg('$envHttpTimeout', '%env(int:ASSISTANT_HTTP_TIMEOUT)%')
        ->arg('$envNumCtx', '%env(int:ASSISTANT_NUM_CTX)%')
        ->arg('$envVisionModel', '%env(ASSISTANT_VISION_MODEL)%')
        ->arg('$envProvider', '%env(ASSISTANT_PROVIDER)%');

    $services->set(OllamaChatClient::class)
        ->arg('$baseUrl', '%env(ASSISTANT_OLLAMA_URL)%');

    $services->set(AnthropicChatClient::class)
        ->arg('$apiKey', '%env(ANTHROPIC_API_KEY)%');

    $services->set(OllamaVisionDescriber::class)
        ->arg('$baseUrl', '%env(ASSISTANT_OLLAMA_URL)%');
};
