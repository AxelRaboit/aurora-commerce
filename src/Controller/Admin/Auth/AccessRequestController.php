<?php

declare(strict_types=1);

namespace App\Controller\Admin\Auth;

use App\Contract\Auth\AccessRequestManagerInterface;
use App\DTO\Auth\AccessRequestInput;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Enum\HttpMethodEnum;
use App\Repository\SettingRepository;
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
        private readonly SettingRepository $settingRepository,
    ) {}

    #[Route('/access-request', name: 'admin_access_request')]
    public function __invoke(Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $accessRequestEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::AdminAccessRequestEnabled->value, true);

        if (!$accessRequestEnabled || !$request->isMethod(HttpMethodEnum::Post->value)) {
            return $this->render('admin/auth/access_request.html.twig', [
                'accessRequestEnabled' => $accessRequestEnabled,
                'errors' => [],
                'values' => [],
            ]);
        }

        $input = AccessRequestInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->render('admin/auth/access_request.html.twig', [
                'accessRequestEnabled' => true,
                'errors' => $errors,
                'values' => $request->request->all(),
            ]);
        }

        $this->accessRequestManager->create($input->email, $input->name, $input->message);
        $this->addFlash('success', $this->translator->trans('admin.auth.access_request.success_toast'));

        return $this->redirectToRoute('admin_login');
    }
}
