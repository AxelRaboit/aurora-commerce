<?php

declare(strict_types=1);

namespace Aurora\Module\General\Search\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Search\BackendSearchProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Backend global search (sidemenu). A pure aggregator: every result section is
 * contributed by a module-owned {@see BackendSearchProviderInterface}, so the
 * General shell holds no domain knowledge and imports no business module.
 */
#[Route('/backend/general/search', name: 'backend_general_search')]
#[IsGranted('general.search.view')]
class SearchController extends AbstractController
{
    use JsonResponseTrait;

    /**
     * @param iterable<BackendSearchProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('aurora.backend_search_provider')]
        private readonly iterable $providers,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = mb_trim((string) $request->query->get('q', ''));
        if ('' === $query) {
            return $this->jsonSuccess([]);
        }

        $sections = [];
        foreach ($this->providers as $provider) {
            $sections = [...$sections, ...$provider->search($query)];
        }

        return $this->jsonSuccess($sections);
    }
}
