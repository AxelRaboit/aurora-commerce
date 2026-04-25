<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\CommentManagerInterface;
use App\Contract\FormManagerInterface;
use App\Entity\Comment;
use App\Entity\FormTranslation;
use App\Entity\Post;
use App\Entity\PostSlugHistory;
use App\Entity\PostTranslation;
use App\Entity\Taxonomy;
use App\Entity\TaxonomyTerm;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Enum\ReactionTypeEnum;
use App\Manager\CommentReactionManager;
use App\Repository\CommentReactionRepository;
use App\Repository\CommentRepository;
use App\Repository\FormTranslationRepository;
use App\Repository\PostRepository;
use App\Repository\PostSlugHistoryRepository;
use App\Repository\PostTypeRepository;
use App\Repository\SettingRepository;
use App\Repository\TaxonomyRepository;
use App\Serializer\CommentSerializer;
use App\Serializer\FormSerializer;
use App\Service\AlternatesBuilder;
use App\Service\BlocksRenderer;
use App\Service\FormSubmissionValidator;
use App\Service\FrontContext;
use App\Service\HttpCacheService;
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
        private readonly CommentSerializer $commentSerializer,
        private readonly AlternatesBuilder $alternatesBuilder,
        private readonly FormTranslationRepository $formTranslationRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializer $formSerializer,
        private readonly FormSubmissionValidator $formSubmissionValidator,
        private readonly HttpCacheService $httpCache,
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
            'alternates' => $this->alternatesBuilder->forRoute('front_home'),
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

        $lastModified = $post->getUpdatedAt();
        if (($notModified = $this->httpCache->checkNotModified($request, $lastModified)) instanceof Response) {
            return $notModified;
        }

        $response = $this->renderPost($post, $locale);
        $this->httpCache->setPublicCache($response, $lastModified);

        return $response;
    }

    #[Route('/{locale}/{postTypeSlug}', name: 'front_archive', requirements: ['locale' => '[a-z]{2}'], priority: 3)]
    public function archive(string $locale, string $postTypeSlug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $postType = $this->postTypeRepository->findOneBy(['slug' => $postTypeSlug]);
        if (null === $postType || !$postType->hasArchive()) {
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
            'alternates' => $this->alternatesBuilder->forRoute('front_archive', ['postTypeSlug' => $postType->getSlug()]),
        ]);

        $this->httpCache->setSharedCache($response);

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

        if (!$term instanceof TaxonomyTerm) {
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
            'alternates' => $this->alternatesBuilder->forTerm($taxonomy, $term),
        ]);

        $this->httpCache->setSharedCache($response);

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

        $tree = $this->commentSerializer->buildFrontTree($allComments, $reactionCountsMap);

        return $this->json(['ok' => true, ...$tree]);
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

    #[Route('/{locale}/forms/{slug}', name: 'front_form', requirements: ['locale' => '[a-z]{2}'], priority: 7)]
    public function showForm(string $locale, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $translation = $this->findActiveFormTranslation($locale, $slug);
        if (!$translation instanceof FormTranslation) {
            throw $this->createNotFoundException();
        }

        $form = $translation->getForm();
        $fields = array_values(array_map(
            fn ($field): array => $this->formSerializer->serializeFieldForLocale($field, $locale),
            $form->getFields()->toArray(),
        ));

        $response = $this->render($this->themeResolver->resolve('form'), [
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
            'form' => $form,
            'translation' => $translation,
            'fields' => $fields,
        ]);

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/forms/{slug}/submit', name: 'front_form_submit', requirements: ['locale' => '[a-z]{2}'], methods: ['POST'], priority: 8)]
    public function submitForm(string $locale, string $slug, Request $request): JsonResponse
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        $translation = $this->findActiveFormTranslation($locale, $slug);
        if (!$translation instanceof FormTranslation) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $form = $translation->getForm();
        $payload = $request->toArray();

        $errors = $this->formSubmissionValidator->validate($form, $payload);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors]);
        }

        $submittedData = $this->formSubmissionValidator->extractSubmittedData($form, $payload);
        $ip = $request->getClientIp() ?? '';
        $this->formManager->submit($form, $submittedData, $locale, $ip);

        return $this->json(['ok' => true]);
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
            $allCommentIds[] = (int) $comment->getId();
            $commentMap[(int) $comment->getId()] = $comment;
            if (null === $comment->getParent()) {
                $rootComments[] = $comment;
            }
        }

        foreach ($allComments as $comment) {
            if (null !== $comment->getParent()) {
                $rootId = $this->commentSerializer->findRootId($comment, $commentMap);
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
            'alternates' => $this->alternatesBuilder->forPost($post),
            'commentsEnabled' => $commentsEnabled,
            'comments' => $rootComments,
            'commentReplies' => $commentReplies,
            'commentErrors' => $commentErrors,
            'reactionCounts' => $reactionCounts,
            'reactionEmojis' => $reactionEmojis,
        ]);

        return $this->withI18nHeaders($response, $locale);
    }

    private function findActiveFormTranslation(string $locale, string $slug): ?FormTranslation
    {
        $translation = $this->formTranslationRepository->findOneByLocaleAndSlug($locale, $slug);
        if (!$translation instanceof FormTranslation || !$translation->getForm()->isActive()) {
            return null;
        }

        return $translation;
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
}
