<?php

declare(strict_types=1);

namespace Aurora\Core\Profile\Controller;

use Aurora\Core\Auth\DTO\ChangePasswordInput;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Profile\View\ProfileViewBuilder;
use Aurora\Core\User\Contract\UserManagerInterface;
use Aurora\Core\User\DTO\MoodInput;
use Aurora\Core\User\DTO\UpdateProfileInput;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Manager\UserProfilePhotoManager;
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/backend/profile', name: 'backend_profile')]
#[IsGranted(UserRoleEnum::Admin->value)]
final class ProfileController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly TranslatorInterface $translator,
        private readonly UserProfilePhotoManager $userProfilePhotoManager,
        private readonly ProfileViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('@Core/backend/profile/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $input = UpdateProfileInput::fromArray($data);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->userManager->update($user, $input->name, $input->email);

        return $this->jsonSuccess();
    }

    #[Route('/password', name: '_password', methods: [HttpMethodEnum::Post->value])]
    public function password(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $input = ChangePasswordInput::fromArray($data);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        if (!$this->userManager->isPasswordValid($user, $input->currentPassword)) {
            return $this->jsonInvalidInput([
                'current_password' => $this->translator->trans('backend.profile.errors.current_password_invalid'),
            ], Response::HTTP_OK);
        }

        $this->userManager->changePassword($user, $input->password);

        return $this->jsonSuccess();
    }

    #[Route('/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Request $request, TokenStorageInterface $tokenStorage): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid('profile_delete', $data['_token'] ?? '')) {
            return $this->jsonFailure($this->translator->trans('backend.profile.errors.invalid_csrf'), Response::HTTP_FORBIDDEN);
        }

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        $this->userManager->delete($user);

        return $this->jsonSuccess();
    }

    #[Route('/mood', name: '_mood', methods: [HttpMethodEnum::Post->value])]
    public function mood(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $input = MoodInput::fromRequest($request);
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->userManager->changeMoodMessage($user, $input->moodMessage);

        return $this->jsonSuccess(['moodMessage' => $user->getMoodMessage()]);
    }

    #[Route('/photo', name: '_photo_upload', methods: [HttpMethodEnum::Post->value])]
    public function uploadPhoto(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $file = $request->files->get('photo');
        if (null === $file) {
            return $this->jsonInvalidInput(['photo' => 'backend.users.photo.errors.missing'], Response::HTTP_OK);
        }

        try {
            $this->userProfilePhotoManager->upload($user, $file);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['photo' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['profilePhotoUrl' => $user->getProfilePhotoUrl()]);
    }

    #[Route('/photo/delete', name: '_photo_delete', methods: [HttpMethodEnum::Post->value])]
    public function deletePhoto(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->userProfilePhotoManager->delete($user);

        return $this->jsonSuccess(['profilePhotoUrl' => null]);
    }

    #[Route('/locale', name: '_locale', methods: [HttpMethodEnum::Post->value])]
    public function locale(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $locale = LocaleEnum::tryFrom($data['locale'] ?? '');

        if (null === $locale) {
            return $this->jsonFailure($this->translator->trans('backend.profile.errors.invalid_locale'));
        }

        $request->getSession()->set('_locale', $locale->value);
        $this->userManager->changeLocaleEnum($user, $locale);

        return $this->jsonSuccess();
    }
}
