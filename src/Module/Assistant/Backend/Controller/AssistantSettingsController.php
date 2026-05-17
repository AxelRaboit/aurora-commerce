<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Backend\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/backend/assistant/settings', name: 'backend_assistant_settings')]
#[IsGranted('configuration.settings.manage')]
final class AssistantSettingsController extends AbstractController
{
    use JsonResponseTrait;

    private const array ANTHROPIC_MODELS = [
        'claude-haiku-4-5',
        'claude-sonnet-4-6',
        'claude-opus-4-7',
    ];

    public function __construct(
        #[Autowire('%env(ASSISTANT_OLLAMA_URL)%')]
        private readonly string $ollamaUrl,
    ) {}

    /**
     * Returns available models for the given provider.
     * Used by the dynamic AssistantSettingsTab Vue component.
     */
    #[Route('/models', name: '_models', methods: [HttpMethodEnum::Get->value])]
    public function models(Request $request): JsonResponse
    {
        $provider = mb_trim((string) $request->query->get('provider', 'ollama'));

        if ('anthropic' === $provider) {
            return $this->jsonSuccess(['models' => self::ANTHROPIC_MODELS]);
        }

        return $this->jsonSuccess(['models' => $this->fetchOllamaModels()]);
    }

    /** @return list<string> */
    private function fetchOllamaModels(): array
    {
        $url = mb_rtrim($this->ollamaUrl, '/').'/api/tags';
        $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);

        try {
            $body = @file_get_contents($url, false, $ctx);
            if (false === $body) {
                return [];
            }

            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $models = array_map(
                static fn (array $m): string => (string) ($m['name'] ?? ''),
                $data['models'] ?? [],
            );

            return array_values(array_filter($models));
        } catch (Throwable) {
            return [];
        }
    }
}
