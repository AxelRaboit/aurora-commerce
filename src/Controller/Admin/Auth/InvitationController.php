<?php

declare(strict_types=1);

namespace App\Controller\Admin\Auth;

use App\Contract\UserManagerInterface;
use App\DTO\UserSetPasswordInput;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Service\PayloadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvitationController extends AbstractController
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/invitation/{selector}/{token}', name: 'admin_invitation_accept', methods: [HttpMethodEnum::Get->value, HttpMethodEnum::Post->value])]
    public function accept(Request $request, string $selector, string $token): Response
    {
        $user = $this->userManager->findValidInvitation($selector, $token);
        if (!$user instanceof User) {
            $this->addFlash('error', $this->translator->trans('auth.invitation.expired'));

            return $this->redirectToRoute('admin_login');
        }

        if ($request->isMethod(HttpMethodEnum::Post->value)) {
            $input = new UserSetPasswordInput(
                password: (string) $request->request->get('password', ''),
                passwordConfirm: (string) $request->request->get('password_confirm', ''),
            );

            $errors = $this->payloadValidator->errors($input);
            if ([] !== $errors) {
                return $this->render('admin/auth/invitation_accept.html.twig', [
                    'user' => $user,
                    'selector' => $selector,
                    'token' => $token,
                    'errors' => $errors,
                ]);
            }

            $this->userManager->consumeInvitation($user, $input->password);

            $this->security->login($user);

            $this->addFlash('success', $this->translator->trans('auth.invitation.success'));

            return new RedirectResponse($this->generateUrl('admin_dashboard'));
        }

        return $this->render('admin/auth/invitation_accept.html.twig', [
            'user' => $user,
            'selector' => $selector,
            'token' => $token,
            'errors' => [],
        ]);
    }
}
