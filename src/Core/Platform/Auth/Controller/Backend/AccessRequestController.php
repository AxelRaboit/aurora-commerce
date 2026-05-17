<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\Controller\Backend;

use Aurora\Core\Platform\Auth\Dto\AccessRequestInput;
use Aurora\Core\Platform\Auth\Manager\AccessRequestManagerInterface;
use Aurora\Core\Platform\Auth\View\AccessRequestViewBuilder;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Validation\Service\PayloadValidator;
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
        private readonly AccessRequestViewBuilder $viewBuilder,
    ) {}

    #[Route('/backend/access-request', name: 'backend_access_request')]
    public function __invoke(Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('backend_dashboard');
        }

        $accessRequestEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::AdminAccessRequestEnabled->value, true);

        if (!$accessRequestEnabled || !$request->isMethod(HttpMethodEnum::Post->value)) {
            return $this->render('@Core/backend/auth/access_request.html.twig', $this->viewBuilder->formView($accessRequestEnabled));
        }

        $input = AccessRequestInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->render('@Core/backend/auth/access_request.html.twig', $this->viewBuilder->formView(true, $errors, $request->request->all()));
        }

        $this->accessRequestManager->create($input->email, $input->name, $input->message);
        $this->addFlash('success', $this->translator->trans('backend.auth.access_request.success_toast'));

        return $this->redirectToRoute('backend_login');
    }
}
