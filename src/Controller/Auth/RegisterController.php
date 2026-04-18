<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Contract\UserManagerInterface;
use App\DTO\RegisterInput;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly ValidatorInterface $validator,
        private readonly SettingRepository $settingRepository,
    ) {}

    #[Route('/register', name: 'app_register')]
    public function __invoke(Request $request, Security $security): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $registrationEnabled = '1' === $this->settingRepository->get('registration_enabled', '0');

        if (!$registrationEnabled || !$request->isMethod('POST')) {
            return $this->render('auth/register.html.twig', [
                'registrationEnabled' => $registrationEnabled,
                'errors' => [],
                'values' => [],
            ]);
        }

        $input = RegisterInput::fromRequest($request);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $field = $violation->getPropertyPath();
                $errors[$field] ??= $violation->getMessage();
            }

            return $this->render('auth/register.html.twig', [
                'registrationEnabled' => true,
                'errors' => $errors,
                'values' => $request->request->all(),
            ]);
        }

        $user = $this->userManager->create($input->name, $input->email, $input->password);
        $security->login($user, 'form_login', 'main');

        return $this->redirectToRoute('admin_dashboard');
    }
}
