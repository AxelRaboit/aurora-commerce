<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostTranslation;
use App\Entity\TaxonomyTerm;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Repository\PostRepository;
use App\Repository\PostSlugHistoryRepository;
use App\Repository\PostTypeRepository;
use App\Repository\TaxonomyRepository;
use App\Repository\TaxonomyTermRepository;
use App\Service\BlocksRenderer;
use App\Service\FrontContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostTypeRepository $postTypeRepository,
        private readonly PostSlugHistoryRepository $slugHistoryRepository,
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly TaxonomyTermRepository $termRepository,
        private readonly BlocksRenderer $blocksRenderer,
        private readonly FrontContext $frontContext,
    ) {}

    #[Route('/', name: 'front_root', priority: 10)]
    public function root(): RedirectResponse
    {
        return $this->redirectToRoute('front_home', ['locale' => $this->frontContext->defaultLocale()]);
    }

    #[Route('/{locale}', name: 'front_home', requirements: ['locale' => '[a-z]{2}'], priority: 9)]
    public function home(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $homepageId = $this->frontContext->homepagePostId();
        if (null !== $homepageId) {
            $post = $this->postRepository->find($homepageId);
            if (null !== $post && $post->isPublished() && !$post->isTrashed()) {
                return $this->renderPost($post, $locale);
            }
        }

        $postType = $this->postTypeRepository->findOneBy(['slug' => 'article']);
        $result = null !== $postType
            ? $this->postRepository->findPublishedByPostType($postType->getId(), (int) $request->query->get('page', 1), $this->postsPerPage(), $locale)
            : ['items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1];

        return $this->render('front/home.html.twig', [
            'locale' => $locale,
            'context' => $this->frontContext,
            'posts' => $result,
            'postType' => $postType,
        ]);
    }

    #[Route('/{locale}/{postTypeSlug}/{slug}', name: 'front_post', requirements: ['locale' => '[a-z]{2}'], priority: 5)]
    public function post(string $locale, string $postTypeSlug, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);

        if (null === $post) {
            $redirect = $this->tryRedirectFromHistory($locale, $slug, $postTypeSlug);
            if (null !== $redirect) {
                return $redirect;
            }
            throw $this->createNotFoundException();
        }

        if ($post->getPostType()->getSlug() !== $postTypeSlug) {
            return $this->redirectToRoute('front_post', [
                'locale' => $locale,
                'postTypeSlug' => $post->getPostType()->getSlug(),
                'slug' => $slug,
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->renderPost($post, $locale);
    }

    #[Route('/{locale}/{postTypeSlug}', name: 'front_archive', requirements: ['locale' => '[a-z]{2}'], priority: 3)]
    public function archive(string $locale, string $postTypeSlug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $postType = $this->postTypeRepository->findOneBy(['slug' => $postTypeSlug]);
        if (null === $postType || !$postType->hasArchive()) {
            // Might be a taxonomy slug instead → try term archive fallback
            $taxonomy = $this->taxonomyRepository->findOneBySlug($postTypeSlug);
            if (null !== $taxonomy) {
                throw $this->createNotFoundException();
            }
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $result = $this->postRepository->findPublishedByPostType($postType->getId(), $page, $this->postsPerPage(), $locale);

        return $this->render('front/archive.html.twig', [
            'locale' => $locale,
            'context' => $this->frontContext,
            'postType' => $postType,
            'posts' => $result,
        ]);
    }

    #[Route('/{locale}/{taxonomySlug}/{termSlug}', name: 'front_term', requirements: ['locale' => '[a-z]{2}'], priority: 4)]
    public function term(string $locale, string $taxonomySlug, string $termSlug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $taxonomy = $this->taxonomyRepository->findOneBySlug($taxonomySlug);
        if (null === $taxonomy) {
            throw $this->createNotFoundException();
        }

        $term = null;
        foreach ($taxonomy->getTerms() as $candidate) {
            if ($candidate->getTranslation($locale)?->getSlug() === $termSlug) {
                $term = $candidate;
                break;
            }
        }
        if (null === $term) {
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $result = $this->postRepository->findPublishedByTerm($term->getId(), $page, $this->postsPerPage(), $locale);

        return $this->render('front/term.html.twig', [
            'locale' => $locale,
            'context' => $this->frontContext,
            'taxonomy' => $taxonomy,
            'term' => $term,
            'posts' => $result,
        ]);
    }

    private function renderPost(Post $post, string $locale): Response
    {
        $translation = $post->getTranslation($locale) ?? $post->getTranslation($this->frontContext->defaultLocale());
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        return $this->render('front/post.html.twig', [
            'locale' => $locale,
            'context' => $this->frontContext,
            'post' => $post,
            'translation' => $translation,
            'content' => $this->blocksRenderer->render($translation->getBlocks()),
        ]);
    }

    private function tryRedirectFromHistory(string $locale, string $slug, string $postTypeSlug): ?RedirectResponse
    {
        $historyEntry = $this->slugHistoryRepository->findOneByLocaleAndSlug($locale, $slug);
        if (null === $historyEntry) {
            return null;
        }
        $currentSlug = $historyEntry->getPost()->getTranslation($locale)?->getSlug();
        if (null === $currentSlug || '' === $currentSlug) {
            return null;
        }

        return $this->redirectToRoute('front_post', [
            'locale' => $locale,
            'postTypeSlug' => $historyEntry->getPost()->getPostType()->getSlug(),
            'slug' => $currentSlug,
        ], Response::HTTP_MOVED_PERMANENTLY);
    }

    private function assertActiveLocale(string $locale): void
    {
        if (!$this->frontContext->isLocaleActive($locale)) {
            throw $this->createNotFoundException(sprintf('Locale "%s" is not active.', $locale));
        }
    }

    private function postsPerPage(): int
    {
        return (int) ($this->frontContext->setting(ApplicationParameterEnum::PostsPerPage->value, '10') ?? 10);
    }
}
