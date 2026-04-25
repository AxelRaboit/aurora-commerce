<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\CommentManagerInterface;
use App\Entity\Comment;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\CommentRepository;
use App\Repository\SettingRepository;
use App\Serializer\CommentSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/comments', name: 'admin_comments')]
#[IsGranted(UserRoleEnum::Editor->value)]
final class CommentsController extends AbstractController
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly CommentManagerInterface $commentManager,
        private readonly CommentSerializer $commentSerializer,
        private readonly SettingRepository $settingRepository,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $stats = $this->commentRepository->countByStatus();
        $moderationEnabled = '1' === $this->settingRepository->get(ApplicationParameterEnum::CommentModerationEnabled->value, '1');

        return $this->render('admin/comments/index.html.twig', [
            'stats' => $stats,
            'moderationEnabled' => $moderationEnabled,
        ]);
    }

    #[Route('/toggle-moderation', name: '_toggle_moderation', methods: [HttpMethodEnum::Post->value])]
    public function toggleModeration(): JsonResponse
    {
        $current = '1' === $this->settingRepository->get(ApplicationParameterEnum::CommentModerationEnabled->value, '1');
        $this->settingRepository->set(ApplicationParameterEnum::CommentModerationEnabled->value, $current ? '0' : '1');

        return $this->json(['ok' => true, 'moderationEnabled' => !$current]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $status = mb_trim((string) $request->query->get('status', ''));

        $result = $this->commentRepository->findPaginatedForAdmin($page, 20, $status ?: null);

        $items = array_map(
            $this->commentSerializer->serialize(...),
            $result['items'],
        );

        return $this->json([
            'ok' => true,
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/{id}/approve', name: '_approve', methods: [HttpMethodEnum::Post->value])]
    public function approve(Comment $comment): JsonResponse
    {
        $this->commentManager->approve($comment);

        return $this->json(['ok' => true, 'comment' => $this->commentSerializer->serialize($comment)]);
    }

    #[Route('/{id}/spam', name: '_spam', methods: [HttpMethodEnum::Post->value])]
    public function spam(Comment $comment): JsonResponse
    {
        $this->commentManager->spam($comment);

        return $this->json(['ok' => true, 'comment' => $this->commentSerializer->serialize($comment)]);
    }

    #[Route('/{id}', name: '_delete', methods: [HttpMethodEnum::Delete->value])]
    public function delete(Comment $comment): JsonResponse
    {
        $this->commentManager->delete($comment);

        return $this->json(['ok' => true]);
    }
}
