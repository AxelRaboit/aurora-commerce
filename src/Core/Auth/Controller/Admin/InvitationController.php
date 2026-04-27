<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\User\Contract\UserManagerInterface;
use Aurora\Core\User\DTO\UserSetPasswordInput;
use Aurora\Core\User\Entity\User;
use Aurora\Core\Validation\Service\PayloadValidator;
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
            $this->addFlash('error', $this->translator->trans('admin.auth.invitation.expired'));

            return $this->redirectToRoute('admin_login');
        }

        if ($request->isMethod(HttpMethodEnum::Post->value)) {
            $input = new UserSetPasswordInput(
                password: (string) $request->request->get('password', ''),
                passwordConfirm: (string) $request->request->get('password_confirm', ''),
            );

            $errors = $this->payloadValidator->errors($input);
            if ([] !== $errors) {
                return $this->render('@Core/admin/auth/invitation_accept.html.twig', [
                    'user' => $user,
                    'selector' => $selector,
                    'token' => $token,
                    'errors' => $errors,
                ]);
            }

            $this->userManager->consumeInvitation($user, $input->password);

            $this->security->login($user);

            $this->addFlash('success', $this->translator->trans('admin.auth.invitation.success'));

            return new RedirectResponse($this->generateUrl('admin_dashboard'));
        }

        return $this->render('@Core/admin/auth/invitation_accept.html.twig', [
            'user' => $user,
            'selector' => $selector,
            'token' => $token,
            'errors' => [],
        ]);
    }
}
