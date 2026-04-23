<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Contract\AccessRequestManagerInterface;
use App\DTO\AccessRequestInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AccessRequestController extends AbstractController
{
    public function __construct(
        private readonly AccessRequestManagerInterface $accessRequestManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/access-request', name: 'app_access_request')]
    public function __invoke(Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if (!$request->isMethod('POST')) {
            return $this->render('auth/access_request.html.twig', [
                'errors' => [],
                'values' => [],
            ]);
        }

        $input = AccessRequestInput::fromRequest($request);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $field = $violation->getPropertyPath();
                $errors[$field] ??= $violation->getMessage();
            }

            return $this->render('auth/access_request.html.twig', [
                'errors' => $errors,
                'values' => $request->request->all(),
            ]);
        }

        $this->accessRequestManager->create($input->email, $input->name, $input->message);
        $this->addFlash('success', $this->translator->trans('auth.access_request.success_toast'));

        return $this->redirectToRoute('app_login');
    }
}
