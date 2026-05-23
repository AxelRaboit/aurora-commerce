<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetPresetInputFactoryInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetPresetApplyModeEnum;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetPresetManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetPresetRepository;
use Aurora\Module\PersonalFinance\Budget\Serializer\PersonalFinanceBudgetPresetSerializerInterface;
use Aurora\Module\PersonalFinance\Budget\View\PersonalFinanceBudgetPresetsViewBuilder;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.budget_presets.use')]
final class PersonalFinanceBudgetPresetsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceBudgetPresetManagerInterface $presetManager,
        private readonly PersonalFinanceBudgetPresetRepository $presetRepository,
        private readonly PersonalFinanceBudgetPresetSerializerInterface $presetSerializer,
        private readonly PersonalFinanceBudgetPresetInputFactoryInterface $presetInputFactory,
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PersonalFinanceBudgetManagerInterface $budgetManager,
        private readonly PersonalFinanceBudgetPresetsViewBuilder $viewBuilder,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/budget-presets', name: '_budget_presets', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $walletId = $request->query->getInt('walletId') ?: null;

        return $this->render(
            '@PersonalFinance/backend/budget_presets/index.html.twig',
            $this->viewBuilder->indexView($user, $walletId),
        );
    }

    #[Route('/wallets/{walletId}/budget-presets/list', name: '_budget_presets_list', methods: [HttpMethodEnum::Get->value])]
    public function list(int $walletId): JsonResponse
    {
        $wallet = $this->loadAccessibleWallet($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $presets = array_map(
            $this->presetSerializer->serialize(...),
            $this->presetRepository->findByWallet($wallet),
        );

        return $this->jsonSuccess(['presets' => $presets]);
    }

    #[Route('/wallets/{walletId}/budget-presets/create', name: '_budget_presets_create', methods: [HttpMethodEnum::Post->value])]
    public function create(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->presetInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $preset = $this->presetManager->create($user, $wallet, $input);

        return $this->jsonSuccess(['preset' => $this->presetSerializer->serialize($preset)]);
    }

    #[Route('/budget-presets/{id}/update', name: '_budget_presets_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        $preset = $this->loadAccessiblePreset($id, PersonalFinanceWalletVoter::EDIT_TRANSACTIONS);
        if (!$preset instanceof PersonalFinanceBudgetPresetInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->presetInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->presetManager->update($preset, $input);

        return $this->jsonSuccess(['preset' => $this->presetSerializer->serialize($preset)]);
    }

    #[Route('/budget-presets/{id}/apply', name: '_budget_presets_apply', methods: [HttpMethodEnum::Post->value])]
    public function apply(int $id, Request $request): JsonResponse
    {
        $preset = $this->loadAccessiblePreset($id, PersonalFinanceWalletVoter::EDIT_TRANSACTIONS);
        if (!$preset instanceof PersonalFinanceBudgetPresetInterface) {
            return $this->jsonNotFound();
        }

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $payload = $this->decodeJson($request);
        $month = $this->resolveMonth(is_string($payload['month'] ?? null) ? $payload['month'] : null);
        $modeValue = is_string($payload['mode'] ?? null) ? $payload['mode'] : PersonalFinanceBudgetPresetApplyModeEnum::Append->value;
        $mode = PersonalFinanceBudgetPresetApplyModeEnum::tryFrom($modeValue) ?? PersonalFinanceBudgetPresetApplyModeEnum::Append;

        $budget = $this->budgetManager->ensureForMonth($user, $preset->getWallet(), $month);
        $inserted = $this->presetManager->applyToMonth($preset, $budget, $mode);

        return $this->jsonSuccess([
            'budgetId' => $budget->getId(),
            'month' => $budget->getMonth()->format('Y-m'),
            'mode' => $mode->value,
            'insertedCount' => $inserted,
        ]);
    }

    #[Route('/budget-presets/{id}/delete', name: '_budget_presets_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $preset = $this->loadAccessiblePreset($id, PersonalFinanceWalletVoter::EDIT_TRANSACTIONS);
        if (!$preset instanceof PersonalFinanceBudgetPresetInterface) {
            return $this->jsonNotFound();
        }

        $this->presetManager->delete($preset);

        return $this->jsonSuccess();
    }

    /**
     * "Save current month as preset" — capture the items of an existing
     * budget month onto a new named preset, without retyping. Called
     * from the Budget page toolbar.
     */
    #[Route('/wallets/{walletId}/budget-presets/save-from-month', name: '_budget_presets_save_from_month', methods: [HttpMethodEnum::Post->value])]
    public function saveFromMonth(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $payload = $this->decodeJson($request);
        $name = is_string($payload['name'] ?? null) ? mb_trim($payload['name']) : '';
        if ('' === $name) {
            return $this->jsonInvalidInput(['name' => 'personal_finance.budget_presets.errors.name_required']);
        }

        $description = is_string($payload['description'] ?? null) ? (mb_trim($payload['description']) ?: null) : null;
        $month = $this->resolveMonth(is_string($payload['month'] ?? null) ? $payload['month'] : null);
        $budget = $this->budgetManager->ensureForMonth($user, $wallet, $month);

        $preset = $this->presetManager->createFromBudget($user, $budget, $name, $description);

        return $this->jsonSuccess(['preset' => $this->presetSerializer->serialize($preset)]);
    }

    private function loadAccessibleWallet(int $walletId): ?PersonalFinanceWalletInterface
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return null;
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $wallet);

        return $wallet;
    }

    private function loadAccessiblePreset(int $id, string $attribute): ?PersonalFinanceBudgetPresetInterface
    {
        $preset = $this->presetRepository->find($id);
        if (!$preset instanceof PersonalFinanceBudgetPresetInterface) {
            return null;
        }

        $this->denyAccessUnlessGranted($attribute, $preset->getWallet());

        return $preset;
    }

    private function resolveMonth(?string $monthParam): DateTimeImmutable
    {
        if (null === $monthParam || '' === $monthParam) {
            return new DateTimeImmutable('first day of this month');
        }

        try {
            return new DateTimeImmutable($monthParam.'-01');
        } catch (Exception) {
            return new DateTimeImmutable('first day of this month');
        }
    }
}
