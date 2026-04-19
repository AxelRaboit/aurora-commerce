<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\TagManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\TagInput;
use App\Entity\Tag;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\TagRepository;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/tags', name: 'admin_tags')]
#[IsGranted(UserRoleEnum::Admin->value)]
class TagsController extends AbstractController
{
    use JsonValidationTrait;

    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly TagManagerInterface $tagManager,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query->get('search', ''));
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->tagRepository->findPaginated($page, 20, $search ?: null);

        $items = array_map(
            fn (Tag $tag): array => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'slug' => $tag->getSlug(),
                'createdAt' => $tag->getCreatedAt()->format(DateTimeInterface::ATOM),
            ],
            $result['items'],
        );

        return $this->render('admin/tags/index.html.twig', [
            'tags' => ['items' => $items, 'total' => $result['total'], 'page' => $result['page'], 'totalPages' => $result['totalPages']],
            'search' => $search,
        ]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = TagInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['success' => false, 'errors' => $this->formatViolations($violations)]);
        }

        $tag = $this->tagManager->create($input->name);

        return $this->json(['success' => true, 'tag' => [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'slug' => $tag->getSlug(),
            'createdAt' => $tag->getCreatedAt()->format(DateTimeInterface::ATOM),
        ]]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(Tag $tag, Request $request): JsonResponse
    {
        $input = TagInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['success' => false, 'errors' => $this->formatViolations($violations)]);
        }

        $this->tagManager->update($tag, $input->name);

        return $this->json(['success' => true, 'tag' => [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'slug' => $tag->getSlug(),
            'createdAt' => $tag->getCreatedAt()->format(DateTimeInterface::ATOM),
        ]]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Tag $tag): JsonResponse
    {
        $this->tagManager->delete($tag);

        return $this->json(['success' => true]);
    }
}
