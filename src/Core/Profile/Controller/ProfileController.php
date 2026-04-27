<?php

declare(strict_types=1);

namespace Aurora\Core\Profile\Controller;

use Aurora\Core\Auth\DTO\ChangePasswordInput;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\User\Contract\UserManagerInterface;
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

#[Route('/admin/profile', name: 'profile')]
#[IsGranted(UserRoleEnum::Admin->value)]
final class ProfileController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly TranslatorInterface $translator,
        private readonly UserProfilePhotoManager $userProfilePhotoManager,
    ) {}

    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('@Core/admin/profile/index.html.twig');
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
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $this->userManager->update($user, $input->name, $input->email);

        return $this->json(['success' => true]);
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
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        if (!$this->userManager->isPasswordValid($user, $input->currentPassword)) {
            return $this->json(['success' => false, 'errors' => [
                'current_password' => $this->translator->trans('admin.profile.errors.current_password_invalid'),
            ]]);
        }

        $this->userManager->changePassword($user, $input->password);

        return $this->json(['success' => true]);
    }

    #[Route('/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Request $request, TokenStorageInterface $tokenStorage): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid('profile_delete', $data['_token'] ?? '')) {
            return $this->json([
                'success' => false,
                'error' => $this->translator->trans('admin.profile.errors.invalid_csrf'),
            ], Response::HTTP_FORBIDDEN);
        }

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        $this->userManager->delete($user);

        return $this->json(['success' => true]);
    }

    #[Route('/mood', name: '_mood', methods: [HttpMethodEnum::Post->value])]
    public function mood(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $raw = is_string($data['moodMessage'] ?? null) ? mb_trim($data['moodMessage']) : '';

        if (mb_strlen($raw) > 160) {
            return $this->json(['success' => false, 'errors' => [
                'moodMessage' => $this->translator->trans('admin.profile.mood.errors.too_long'),
            ]]);
        }

        $this->userManager->changeMoodMessage($user, '' === $raw ? null : $raw);

        return $this->json(['success' => true, 'moodMessage' => $user->getMoodMessage()]);
    }

    #[Route('/photo', name: '_photo_upload', methods: [HttpMethodEnum::Post->value])]
    public function uploadPhoto(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $file = $request->files->get('photo');
        if (null === $file) {
            return $this->json(['success' => false, 'errors' => ['photo' => 'admin.users.photo.errors.missing']]);
        }

        try {
            $this->userProfilePhotoManager->upload($user, $file);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['photo' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'profilePhotoUrl' => $user->getProfilePhotoUrl()]);
    }

    #[Route('/photo/delete', name: '_photo_delete', methods: [HttpMethodEnum::Post->value])]
    public function deletePhoto(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->userProfilePhotoManager->delete($user);

        return $this->json(['success' => true, 'profilePhotoUrl' => null]);
    }

    #[Route('/locale', name: '_locale', methods: [HttpMethodEnum::Post->value])]
    public function locale(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $locale = LocaleEnum::tryFrom($data['locale'] ?? '');

        if (null === $locale) {
            return $this->json([
                'success' => false,
                'error' => $this->translator->trans('admin.profile.errors.invalid_locale'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $request->getSession()->set('_locale', $locale->value);
        $this->userManager->changeLocale($user, $locale);

        return $this->json(['success' => true]);
    }
}
