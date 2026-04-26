<?php

declare(strict_types=1);

namespace App\Controller\Admin\Auth;

use App\Contract\AccessRequestManagerInterface;
use App\DTO\AccessRequestInput;
use App\Enum\HttpMethodEnum;
use App\Service\PayloadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AccessRequestController extends AbstractController
{
    public function __construct(
        private readonly AccessRequestManagerInterface $accessRequestManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/access-request', name: 'admin_access_request')]
    public function __invoke(Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if (!$request->isMethod(HttpMethodEnum::Post->value)) {
            return $this->render('admin/auth/access_request.html.twig', [
                'errors' => [],
                'values' => [],
            ]);
        }

        $input = AccessRequestInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->render('admin/auth/access_request.html.twig', [
                'errors' => $errors,
                'values' => $request->request->all(),
            ]);
        }

        $this->accessRequestManager->create($input->email, $input->name, $input->message);
        $this->addFlash('success', $this->translator->trans('auth.access_request.success_toast'));

        return $this->redirectToRoute('admin_login');
    }
}
