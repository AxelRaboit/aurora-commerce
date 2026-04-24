<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\PostManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\PostInput;
use App\Entity\Post;
use App\Entity\PostRevision;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\PostRepository;
use App\Repository\PostRevisionRepository;
use App\Repository\PostTypeRepository;
use App\Repository\TaxonomyRepository;
use App\Serializer\PostRevisionSerializer;
use App\Serializer\PostSerializer;
use App\Serializer\PostTypeSerializer;
use App\Serializer\TaxonomySerializer;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/posts', name: 'admin_posts')]
#[IsGranted(UserRoleEnum::Admin->value)]
class PostsController extends AbstractController
{
    use JsonValidationTrait;

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
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query->get('search', ''));
        $page = max(1, (int) $request->query->get('page', '1'));
        $postTypeId = $request->query->getInt('postTypeId') ?: null;
        $trashed = $request->query->getBoolean('trashed');
        $result = $this->postRepository->findPaginated($page, 20, $search ?: null, $postTypeId, trashed: $trashed);

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

        return $this->render('admin/posts/index.html.twig', [
            'posts' => ['items' => $items, 'total' => $result['total'], 'page' => $result['page'], 'totalPages' => $result['totalPages']],
            'search' => $search,
            'postTypes' => $postTypes,
            'taxonomies' => $taxonomies,
            'trashed' => $trashed,
            'locales' => $this->getParameter('kernel.enabled_locales'),
        ]);
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

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['success' => false, 'errors' => $this->formatViolations($violations)]);
        }

        $post = $this->postManager->create($input);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(Post $post, Request $request): JsonResponse
    {
        $input = PostInput::fromArray($this->decodeJson($request));

        if (!$input->force && null !== $input->version) {
            try {
                $this->entityManager->lock($post, LockMode::OPTIMISTIC, $input->version);
            } catch (OptimisticLockException) {
                return $this->json(['success' => false, 'conflict' => true], Response::HTTP_CONFLICT);
            }
        }

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['success' => false, 'errors' => $this->formatViolations($violations)]);
        }

        $this->postManager->update($post, $input);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Post $post): JsonResponse
    {
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
