<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/notes/post-it', name: 'backend_notes_post_it')]
#[IsGranted('notes.post_it.use')]
final class PostItNotesController extends AbstractController
{
    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Notes/backend/post_it/index.html.twig');
    }
}
