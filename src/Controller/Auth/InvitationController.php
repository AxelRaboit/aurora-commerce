<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Contract\UserManagerInterface;
use App\DTO\UserSetPasswordInput;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvitationController extends AbstractController
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly ValidatorInterface $validator,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/invitation/{selector}/{token}', name: 'app_invitation_accept', methods: [HttpMethodEnum::Get->value, HttpMethodEnum::Post->value])]
    public function accept(Request $request, string $selector, string $token): Response
    {
        $user = $this->userManager->findValidInvitation($selector, $token);
        if (!$user instanceof User) {
            $this->addFlash('error', $this->translator->trans('auth.invitation.expired'));

            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod(HttpMethodEnum::Post->value)) {
            $input = new UserSetPasswordInput(
                password: (string) $request->request->get('password', ''),
                passwordConfirm: (string) $request->request->get('password_confirm', ''),
            );

            $violations = $this->validator->validate($input);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $field = $violation->getPropertyPath();
                    if (!isset($errors[$field])) {
                        $errors[$field] = $this->translator->trans((string) $violation->getMessage());
                    }
                }

                return $this->render('auth/invitation_accept.html.twig', [
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

        return $this->render('auth/invitation_accept.html.twig', [
            'user' => $user,
            'selector' => $selector,
            'token' => $token,
            'errors' => [],
        ]);
    }
}
