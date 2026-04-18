<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Service\InvitationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dev/invitations', name: 'dev_invitations')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class InvitationsController extends AbstractController
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('admin/administration/index.html.twig', [
            'tab' => 'invitations',
        ]);
    }

    #[Route('/send', name: '_send', methods: [HttpMethodEnum::Post->value])]
    public function send(Request $request): Response
    {
        $email = mb_trim((string) $request->request->get('email', ''));

        if ('' === $email) {
            $this->addFlash('error', $this->translator->trans('profile.errors.email_invalid'));

            return $this->redirectToRoute('dev_invitations');
        }

        $this->invitationService->send(
            $email,
            (string) $request->request->get('message', ''),
            (string) $request->request->get('credential_email', ''),
            (string) $request->request->get('credential_password', ''),
        );

        $this->addFlash('success', $this->translator->trans('admin.invitations.sent', ['{email}' => $email]));

        return $this->redirectToRoute('dev_invitations');
    }
}
