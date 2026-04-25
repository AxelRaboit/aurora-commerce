<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\CommentManagerInterface;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostSlugHistory;
use App\Entity\PostTranslation;
use App\Entity\Taxonomy;
use App\Entity\TaxonomyTerm;
use App\Entity\TaxonomyTermTranslation;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Enum\ReactionTypeEnum;
use App\Manager\CommentReactionManager;
use App\Repository\CommentReactionRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\PostSlugHistoryRepository;
use App\Repository\PostTypeRepository;
use App\Repository\SettingRepository;
use App\Repository\TaxonomyRepository;
use App\Service\BlocksRenderer;
use App\Service\FrontContext;
use App\Service\ThemeContext;
use App\Service\ThemeResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use const DATE_ATOM;

class FrontController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostTypeRepository $postTypeRepository,
        private readonly PostSlugHistoryRepository $slugHistoryRepository,
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly BlocksRenderer $blocksRenderer,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly ThemeContext $themeContext,
        private readonly CommentRepository $commentRepository,
        private readonly SettingRepository $settingRepository,
        private readonly CommentManagerInterface $commentManager,
        private readonly ValidatorInterface $validator,
        private readonly CommentReactionRepository $commentReactionRepository,
        private readonly CommentReactionManager $commentReactionManager,
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

        $response = $this->render($this->themeResolver->resolve('home'), [
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
            'posts' => $result,
            'postType' => $postType,
            'alternates' => $this->buildSameRouteAlternates('front_home'),
        ]);

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/{postTypeSlug}/{slug}', name: 'front_post', requirements: ['locale' => '[a-z]{2}'], priority: 5)]
    public function post(string $locale, string $postTypeSlug, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);

        if (!$post instanceof Post) {
            $redirect = $this->tryRedirectFromHistory($locale, $slug, $postTypeSlug);
            if ($redirect instanceof RedirectResponse) {
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
            if ($taxonomy instanceof Taxonomy) {
                throw $this->createNotFoundException();
            }

            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $result = $this->postRepository->findPublishedByPostType($postType->getId(), $page, $this->postsPerPage(), $locale);

        $response = $this->render($this->themeResolver->resolve('archive'), [
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
            'postType' => $postType,
            'posts' => $result,
            'alternates' => $this->buildSameRouteAlternates('front_archive', ['postTypeSlug' => $postType->getSlug()]),
        ]);

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/{taxonomySlug}/{termSlug}', name: 'front_term', requirements: ['locale' => '[a-z]{2}'], priority: 4)]
    public function term(string $locale, string $taxonomySlug, string $termSlug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $taxonomy = $this->taxonomyRepository->findOneBySlug($taxonomySlug);
        if (!$taxonomy instanceof Taxonomy) {
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

        $response = $this->render($this->themeResolver->resolve('term'), [
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
            'taxonomy' => $taxonomy,
            'term' => $term,
            'posts' => $result,
            'alternates' => $this->buildTermAlternates($taxonomy, $term),
        ]);

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/{postTypeSlug}/{slug}/comment', name: 'front_post_comment', requirements: ['locale' => '[a-z]{2}'], methods: ['POST'], priority: 6)]
    public function submitComment(string $locale, string $postTypeSlug, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $commentsEnabled = '1' === $this->settingRepository->get('comments_enabled', '0');
        if (!$commentsEnabled) {
            return $this->redirectToRoute('front_post', ['locale' => $locale, 'postTypeSlug' => $postTypeSlug, 'slug' => $slug]);
        }

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);
        if (!$post instanceof Post) {
            throw $this->createNotFoundException();
        }

        $isJson = str_contains((string) $request->headers->get('Content-Type', ''), 'application/json');
        $payload = $isJson ? $request->toArray() : $request->request->all();

        $authorName = mb_trim((string) ($payload['authorName'] ?? ''));
        $authorEmail = mb_trim((string) ($payload['authorEmail'] ?? ''));
        $content = mb_trim((string) ($payload['content'] ?? ''));

        $commentErrors = [];

        $nameViolations = $this->validator->validate($authorName, [new NotBlank(message: 'comment.errors.name_required'), new Length(max: 100)]);
        foreach ($nameViolations as $violation) {
            $commentErrors['authorName'] = $violation->getMessage();
            break;
        }

        $emailViolations = $this->validator->validate($authorEmail, [new NotBlank(message: 'comment.errors.email_invalid'), new Email(message: 'comment.errors.email_invalid')]);
        foreach ($emailViolations as $violation) {
            $commentErrors['authorEmail'] = $violation->getMessage();
            break;
        }

        $contentViolations = $this->validator->validate($content, [new NotBlank(message: 'comment.errors.content_required'), new Length(max: 2000, maxMessage: 'comment.errors.content_too_long')]);
        foreach ($contentViolations as $violation) {
            $commentErrors['content'] = $violation->getMessage();
            break;
        }

        if ([] !== $commentErrors) {
            if ($isJson) {
                return $this->json(['ok' => false, 'errors' => $commentErrors]);
            }

            return $this->renderPost($post, $locale, $commentErrors);
        }

        $parentComment = null;
        $parentId = (int) ($isJson ? ($request->toArray()['parent_id'] ?? 0) : $request->request->get('parent_id', 0));
        if ($parentId > 0) {
            $parentComment = $this->commentRepository->find($parentId);
            if (
                null === $parentComment
                || $parentComment->getPost()->getId() !== $post->getId()
                || 'approved' !== $parentComment->getStatus()->value
            ) {
                $parentComment = null;
            }
        }

        $this->commentManager->submit($post, $authorName, $authorEmail, $content, $parentComment);

        if ($isJson) {
            return $this->json(['ok' => true]);
        }

        $this->addFlash('commentSuccess', 'comment.success');

        return $this->redirectToRoute('front_post', ['locale' => $locale, 'postTypeSlug' => $postTypeSlug, 'slug' => $slug]);
    }

    #[Route('/{locale}/{postTypeSlug}/{slug}/comments', name: 'front_post_comments_list', requirements: ['locale' => '[a-z]{2}'], methods: ['GET'], priority: 5)]
    public function commentsList(string $locale, string $postTypeSlug, string $slug): JsonResponse
    {
        $this->assertActiveLocale($locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);
        if (!$post instanceof Post) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $commentsEnabled = '1' === $this->settingRepository->get('comments_enabled', '0');
        if (!$commentsEnabled) {
            return $this->json(['ok' => true, 'roots' => [], 'replies' => [], 'reactionEmojis' => []]);
        }

        $allComments = $this->commentRepository->findApprovedByPost($post->getId());

        $allCommentIds = array_map(static fn (Comment $comment): int => (int) $comment->getId(), $allComments);
        $reactionCountsMap = [] !== $allCommentIds
            ? $this->commentReactionRepository->countByComments($allCommentIds)
            : [];

        $serializeForFront = (fn (Comment $comment): array => [
            'id' => $comment->getId(),
            'authorName' => $comment->getAuthorName(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'parentId' => $comment->getParent()?->getId(),
            'parentAuthorName' => $comment->getParent()?->getAuthorName(),
            'reactionCounts' => $reactionCountsMap[$comment->getId()] ?? [],
        ]);

        $commentMap = [];
        foreach ($allComments as $comment) {
            $commentMap[$comment->getId()] = $comment;
        }

        $roots = [];
        $replies = [];

        foreach ($allComments as $comment) {
            if (null === $comment->getParent()) {
                $roots[] = $serializeForFront($comment);
            } else {
                $rootId = $this->findRootId($comment, $commentMap);
                $replies[$rootId][] = $serializeForFront($comment);
            }
        }

        $reactionEmojis = [];
        foreach (ReactionTypeEnum::cases() as $case) {
            $reactionEmojis[$case->value] = $case->emoji();
        }

        return $this->json(['ok' => true, 'roots' => $roots, 'replies' => $replies, 'reactionEmojis' => $reactionEmojis]);
    }

    #[Route('/{locale}/{postTypeSlug}/{slug}/comment/{commentId}/react', name: 'front_comment_react', requirements: ['locale' => '[a-z]{2}'], methods: ['POST'], priority: 5)]
    public function reactToComment(string $locale, string $postTypeSlug, string $slug, int $commentId, Request $request): JsonResponse
    {
        $this->assertActiveLocale($locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);
        if (!$post instanceof Post) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $comment = $this->commentRepository->find($commentId);
        if (
            null === $comment
            || $comment->getPost()->getId() !== $post->getId()
            || 'approved' !== $comment->getStatus()->value
        ) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $typeValue = str_contains((string) $request->headers->get('Content-Type', ''), 'application/json')
            ? (string) ($request->toArray()['type'] ?? '')
            : (string) $request->request->get('type', '');

        $reactionType = ReactionTypeEnum::tryFrom($typeValue);
        if (null === $reactionType) {
            return $this->json(['ok' => false, 'error' => 'Invalid reaction type'], Response::HTTP_BAD_REQUEST);
        }

        $fingerprint = $this->commentReactionManager->generateFingerprint($request);
        $updatedCounts = $this->commentReactionManager->toggle($comment, $reactionType, $fingerprint);

        return $this->json(['ok' => true, 'counts' => $updatedCounts]);
    }

    /**
     * @param array<string, string> $commentErrors
     */
    private function renderPost(Post $post, string $locale, array $commentErrors = []): Response
    {
        $translation = $post->getTranslation($locale);
        if (!$translation instanceof PostTranslation) {
            throw $this->createNotFoundException();
        }

        $commentsEnabled = '1' === $this->settingRepository->get('comments_enabled', '0');
        $allComments = $commentsEnabled ? $this->commentRepository->findApprovedByPost($post->getId()) : [];

        $commentMap = [];
        $rootComments = [];
        $commentReplies = [];
        $allCommentIds = [];

        foreach ($allComments as $comment) {
            $allCommentIds[] = $comment->getId();
            $commentMap[$comment->getId()] = $comment;
            if (null === $comment->getParent()) {
                $rootComments[] = $comment;
            }
        }

        foreach ($allComments as $comment) {
            if (null !== $comment->getParent()) {
                $rootId = $this->findRootId($comment, $commentMap);
                $commentReplies[$rootId][] = $comment;
            }
        }

        $reactionCounts = $commentsEnabled && [] !== $allCommentIds
            ? $this->commentReactionRepository->countByComments($allCommentIds)
            : [];

        $reactionEmojis = [];
        foreach (ReactionTypeEnum::cases() as $reactionCase) {
            $reactionEmojis[$reactionCase->value] = $reactionCase->emoji();
        }

        $response = $this->render($this->themeResolver->resolve('post'), [
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
            'post' => $post,
            'translation' => $translation,
            'content' => $this->blocksRenderer->render($translation->getBlocks()),
            'alternates' => $this->buildPostAlternates($post),
            'commentsEnabled' => $commentsEnabled,
            'comments' => $rootComments,
            'commentReplies' => $commentReplies,
            'commentErrors' => $commentErrors,
            'reactionCounts' => $reactionCounts,
            'reactionEmojis' => $reactionEmojis,
        ]);

        return $this->withI18nHeaders($response, $locale);
    }

    /**
     * @return list<array{locale: string, url: string}>
     */
    private function buildPostAlternates(Post $post): array
    {
        $alternates = [];
        foreach ($this->frontContext->activeLocaleCodes() as $code) {
            $translation = $post->getTranslation($code);
            if (!$translation instanceof PostTranslation) {
                continue;
            }

            $alternates[] = [
                'locale' => $code,
                'url' => $this->generateUrl('front_post', [
                    'locale' => $code,
                    'postTypeSlug' => $post->getPostType()->getSlug(),
                    'slug' => $translation->getSlug(),
                ]),
            ];
        }

        return $alternates;
    }

    /**
     * @param array<string, string> $extraParams
     *
     * @return list<array{locale: string, url: string}>
     */
    private function buildSameRouteAlternates(string $route, array $extraParams = []): array
    {
        $alternates = [];
        foreach ($this->frontContext->activeLocaleCodes() as $code) {
            $alternates[] = [
                'locale' => $code,
                'url' => $this->generateUrl($route, array_merge($extraParams, ['locale' => $code])),
            ];
        }

        return $alternates;
    }

    /**
     * @return list<array{locale: string, url: string}>
     */
    private function buildTermAlternates(Taxonomy $taxonomy, TaxonomyTerm $term): array
    {
        $alternates = [];
        foreach ($this->frontContext->activeLocaleCodes() as $code) {
            $termTranslation = $term->getTranslation($code);
            if (!$termTranslation instanceof TaxonomyTermTranslation) {
                continue;
            }

            if ('' === $termTranslation->getSlug()) {
                continue;
            }

            $alternates[] = [
                'locale' => $code,
                'url' => $this->generateUrl('front_term', [
                    'locale' => $code,
                    'taxonomySlug' => $taxonomy->getSlug(),
                    'termSlug' => $termTranslation->getSlug(),
                ]),
            ];
        }

        return $alternates;
    }

    private function withI18nHeaders(Response $response, string $locale): Response
    {
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    private function tryRedirectFromHistory(string $locale, string $slug, string $postTypeSlug): ?RedirectResponse
    {
        $historyEntry = $this->slugHistoryRepository->findOneByLocaleAndSlug($locale, $slug);
        if (!$historyEntry instanceof PostSlugHistory) {
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

    /**
     * Walks up the parent chain to find the root comment ID.
     */
    /**
     * @param array<int, Comment> $commentMap pre-built id→comment map
     */
    private function findRootId(Comment $comment, array $commentMap): int
    {
        $current = $comment;
        while (null !== $current->getParent()) {
            $parentId = $current->getParent()->getId();
            $current = $commentMap[$parentId] ?? $current->getParent();
        }

        return $current->getId();
    }
}
