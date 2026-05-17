<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Frontend\Controller\LocaleTrait;
use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Configuration\Theme\Service\ThemeResolver;
use Aurora\Module\Ged\Document\View\Frontend\DocumentsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{locale}/ged', name: 'frontend_ged', requirements: ['locale' => '[a-z]{2}'])]
class DocumentsController extends AbstractController
{
    use LocaleTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly DocumentsViewBuilder $viewBuilder,
        private readonly ThemeResolver $themeResolver,
        private readonly Context $context,
    ) {}

    #[Route('', name: '_index', methods: [HttpMethodEnum::Get->value])]
    public function index(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $page = max(1, $request->query->getInt('page', 1));
        $searchPath = $this->generateUrl('frontend_ged_search', ['locale' => $locale]);

        return $this->render(
            $this->themeResolver->resolve('ged/documents/index'),
            $this->viewBuilder->indexView($locale, $page, $searchPath),
        );
    }

    #[Route('/search', name: '_search', methods: [HttpMethodEnum::Get->value])]
    public function search(string $locale, Request $request): JsonResponse
    {
        $this->assertActiveLocale($this->context, $locale);

        $query = mb_trim($request->query->getString('q', ''));
        $page = max(1, $request->query->getInt('page', 1));

        return $this->jsonSuccess(
            $this->viewBuilder->pageData($page, '' !== $query ? $query : null),
        );
    }
}
