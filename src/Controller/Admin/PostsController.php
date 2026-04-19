<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\PostManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\PostInput;
use App\Entity\Post;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\PostRepository;
use App\Repository\PostTypeRepository;
use App\Repository\TagRepository;
use App\Serializer\PostSerializer;
use App\Serializer\PostTypeSerializer;
use App\Serializer\TagSerializer;
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
        private readonly TagRepository $tagRepository,
        private readonly PostManagerInterface $postManager,
        private readonly PostSerializer $postSerializer,
        private readonly PostTypeSerializer $postTypeSerializer,
        private readonly TagSerializer $tagSerializer,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query->get('search', ''));
        $page = max(1, (int) $request->query->get('page', '1'));
        $postTypeId = $request->query->getInt('postTypeId') ?: null;
        $result = $this->postRepository->findPaginated($page, 20, $search ?: null, $postTypeId);

        $items = array_map(
            $this->postSerializer->serialize(...),
            $result['items'],
        );

        $postTypes = array_map(
            $this->postTypeSerializer->serialize(...),
            $this->postTypeRepository->findAll(),
        );

        $allTags = array_map(
            $this->tagSerializer->serialize(...),
            $this->tagRepository->findAll(),
        );

        return $this->render('admin/posts/index.html.twig', [
            'posts' => ['items' => $items, 'total' => $result['total'], 'page' => $result['page'], 'totalPages' => $result['totalPages']],
            'search' => $search,
            'postTypes' => $postTypes,
            'allTags' => $allTags,
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
        $input = PostInput::fromArray(json_decode($request->getContent(), true) ?? []);

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
        $input = PostInput::fromArray(json_decode($request->getContent(), true) ?? []);

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
}
