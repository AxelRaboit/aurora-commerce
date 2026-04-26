<?php

declare(strict_types=1);

namespace App\Controller\Admin\Content;

use App\Contract\Post\PostManagerInterface;
use App\Controller\Trait\JsonRequestTrait;
use App\DTO\PaginationRequest;
use App\DTO\Post\PostInput;
use App\Entity\Post;
use App\Entity\PostRevision;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\PostStatusEnum;
use App\Enum\User\UserRoleEnum;
use App\Repository\Post\PostRepository;
use App\Repository\Post\PostRevisionRepository;
use App\Repository\Post\PostTypeRepository;
use App\Repository\Taxonomy\TaxonomyRepository;
use App\Security\Voter\PostVoter;
use App\Serializer\Post\PostRevisionSerializer;
use App\Serializer\Post\PostSerializer;
use App\Serializer\Post\PostTypeSerializer;
use App\Serializer\Taxonomy\TaxonomySerializer;
use App\Service\PayloadValidator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/posts', name: 'admin_posts')]
#[IsGranted(UserRoleEnum::Contributor->value)]
class PostsController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostTypeRepository $postTypeRepository,
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly PostManagerInterface $postManager,
        private readonly PostSerializer $postSerializer,
        private readonly PostTypeSerializer $postTypeSerializer,
        private readonly TaxonomySerializer $taxonomySerializer,
        private readonly PostRevisionRepository $revisionRepository,
        private readonly PostRevisionSerializer $revisionSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $postTypeId = $request->query->getInt('postTypeId') ?: null;
        $trashed = $request->query->getBoolean('trashed');

        $authorId = null;
        if (!$this->isGranted(UserRoleEnum::Editor->value)) {
            $currentUser = $this->getUser();
            $authorId = $currentUser instanceof User ? $currentUser->getId() : null;
        }

        $result = $this->postRepository->findPaginated($pagination->page, 10, $pagination->search, $postTypeId, trashed: $trashed, authorId: $authorId);

        $items = array_map(
            $this->postSerializer->serialize(...),
            $result['items'],
        );

        $postTypes = array_map(
            $this->postTypeSerializer->serialize(...),
            $this->postTypeRepository->findAll(),
        );

        $taxonomies = array_map(
            $this->taxonomySerializer->serializeFull(...),
            $this->taxonomyRepository->findBy([], ['slug' => 'ASC']),
        );

        $payload = ['ok' => true, 'items' => $items, 'total' => $result['total'], 'page' => $result['page'], 'totalPages' => $result['totalPages']];

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('admin/posts/index.html.twig', [
            'posts' => $payload,
            'search' => $pagination->search ?? '',
            'postTypes' => $postTypes,
            'taxonomies' => $taxonomies,
            'trashed' => $trashed,
            'locales' => $this->getParameter('kernel.enabled_locales'),
        ]);
    }

    #[Route('/search', name: '_search', methods: [HttpMethodEnum::Get->value])]
    public function search(Request $request): JsonResponse
    {
        $idsParam = (string) $request->query->get('ids', '');
        if ('' !== $idsParam) {
            $ids = array_values(array_filter(array_map(intval(...), explode(',', $idsParam)), static fn (int $id): bool => $id > 0));
            $results = $this->postRepository->findByIds($ids);
        } else {
            $query = mb_trim((string) $request->query->get('q', ''));
            $excludeId = $request->query->getInt('excludeId') ?: null;
            $postTypeId = $request->query->getInt('postTypeId') ?: null;
            $results = $this->postRepository->searchForReference($query, $excludeId, $postTypeId);
        }

        $items = array_map(
            fn (Post $post): array => [
                'id' => $post->getId(),
                'title' => $post->getTranslation('fr')?->getTitle() ?? ($post->getTranslations()->first() ?: null)?->getTitle(),
                'status' => $post->getStatus()->value,
                'postTypeId' => $post->getPostType()->getId(),
                'postType' => $post->getPostType()->getLabel(),
            ],
            $results,
        );

        return $this->json(['success' => true, 'results' => $items]);
    }

    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(Post $post): JsonResponse
    {
        return $this->json(['success' => true, 'post' => $this->postSerializer->serializeFull($post)]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = PostInput::fromArray($this->decodeJson($request));

        if (!$this->isGranted(UserRoleEnum::Author->value) && PostStatusEnum::Published->value === $input->status) {
            $input = $input->withStatus(PostStatusEnum::PendingReview->value);
        }

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $post = $this->postManager->create($input);
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $post->setAuthor($currentUser);
            $this->entityManager->flush();
        }

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(Post $post, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(PostVoter::EDIT, $post);

        $input = PostInput::fromArray($this->decodeJson($request));

        if (!$this->isGranted(PostVoter::PUBLISH, $post) && PostStatusEnum::Published->value === $input->status) {
            $input = $input->withStatus(PostStatusEnum::PendingReview->value);
        }

        if (!$input->force && null !== $input->version) {
            try {
                $this->entityManager->lock($post, LockMode::OPTIMISTIC, $input->version);
            } catch (OptimisticLockException) {
                return $this->json(['success' => false, 'conflict' => true], Response::HTTP_CONFLICT);
            }
        }

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $this->postManager->update($post, $input);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Post $post): JsonResponse
    {
        $this->denyAccessUnlessGranted(PostVoter::DELETE, $post);

        $this->postManager->delete($post);

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/restore', name: '_restore', methods: [HttpMethodEnum::Post->value])]
    public function restore(Post $post): JsonResponse
    {
        $this->postManager->restore($post);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/force-delete', name: '_force_delete', methods: [HttpMethodEnum::Post->value])]
    public function forceDelete(Post $post): JsonResponse
    {
        $this->postManager->forceDelete($post);

        return $this->json(['success' => true]);
    }

    #[Route('/empty-trash', name: '_empty_trash', methods: [HttpMethodEnum::Post->value])]
    public function emptyTrash(): JsonResponse
    {
        $posts = $this->postRepository->findAllTrashed();
        foreach ($posts as $post) {
            $this->postManager->forceDelete($post);
        }

        return $this->json(['success' => true, 'count' => count($posts)]);
    }

    #[Route('/{id}/revisions', name: '_revisions', methods: [HttpMethodEnum::Get->value])]
    public function listRevisions(Post $post): JsonResponse
    {
        $items = array_map(
            $this->revisionSerializer->serialize(...),
            $this->revisionRepository->findByPost($post),
        );

        return $this->json(['success' => true, 'revisions' => $items]);
    }

    #[Route('/{id}/revisions/{revisionId}', name: '_revision_show', methods: [HttpMethodEnum::Get->value])]
    public function showRevision(Post $post, int $revisionId): JsonResponse
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision instanceof PostRevision || $revision->getPost() !== $post) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['success' => true, 'revision' => $this->revisionSerializer->serializeFull($revision)]);
    }

    #[Route('/{id}/revisions/{revisionId}/restore', name: '_revision_restore', methods: [HttpMethodEnum::Post->value])]
    public function restoreRevision(Post $post, int $revisionId): JsonResponse
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision instanceof PostRevision || $revision->getPost() !== $post) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $this->postManager->restoreRevision($post, $revision);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }
}
