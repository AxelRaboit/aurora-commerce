<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceRecurringTransactionInputFactoryInterface;
use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceScheduledTransactionInputFactoryInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;
use Aurora\Module\PersonalFinance\Recurring\Manager\PersonalFinanceRecurringTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Recurring\Manager\PersonalFinanceScheduledTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceScheduledTransactionRepository;
use Aurora\Module\PersonalFinance\Recurring\Serializer\PersonalFinanceRecurringTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Recurring\Serializer\PersonalFinanceScheduledTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Recurring\View\PersonalFinanceRecurringViewBuilder;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.recurring.use')]
final class PersonalFinanceRecurringController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceRecurringTransactionManagerInterface $recurringManager,
        private readonly PersonalFinanceRecurringTransactionRepository $recurringRepository,
        private readonly PersonalFinanceRecurringTransactionSerializerInterface $recurringSerializer,
        private readonly PersonalFinanceRecurringTransactionInputFactoryInterface $recurringInputFactory,
        private readonly PersonalFinanceScheduledTransactionManagerInterface $scheduledManager,
        private readonly PersonalFinanceScheduledTransactionRepository $scheduledRepository,
        private readonly PersonalFinanceScheduledTransactionSerializerInterface $scheduledSerializer,
        private readonly PersonalFinanceScheduledTransactionInputFactoryInterface $scheduledInputFactory,
        private readonly PersonalFinanceRecurringViewBuilder $viewBuilder,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/recurring', name: '_recurring', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render(
            '@PersonalFinance/backend/recurring/index.html.twig',
            $this->viewBuilder->indexView($user),
        );
    }

    // ----- Recurring CRUD -----

    #[Route('/recurring/create', name: '_recurring_create', methods: [HttpMethodEnum::Post->value])]
    public function createRecurring(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->recurringInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $rec = $this->recurringManager->create($user, $input);
        } catch (DomainException $exception) {
            return $this->jsonInvalidInput(['walletId' => $exception->getMessage()]);
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $rec->getWallet());

        return $this->jsonSuccess(['recurring' => $this->recurringSerializer->serialize($rec)]);
    }

    #[Route('/recurring/{id}/update', name: '_recurring_update', methods: [HttpMethodEnum::Post->value])]
    public function updateRecurring(int $id, Request $request): JsonResponse
    {
        $rec = $this->loadOwnedRecurring($id);
        if (!$rec instanceof PersonalFinanceRecurringTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $rec->getWallet());

        $input = $this->recurringInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->recurringManager->update($rec, $input);
        } catch (DomainException $exception) {
            return $this->jsonInvalidInput(['walletId' => $exception->getMessage()]);
        }

        return $this->jsonSuccess(['recurring' => $this->recurringSerializer->serialize($rec)]);
    }

    #[Route('/recurring/{id}/toggle', name: '_recurring_toggle', methods: [HttpMethodEnum::Post->value])]
    public function toggleRecurring(int $id): JsonResponse
    {
        $rec = $this->loadOwnedRecurring($id);
        if (!$rec instanceof PersonalFinanceRecurringTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $rec->getWallet());

        $this->recurringManager->toggle($rec);

        return $this->jsonSuccess(['recurring' => $this->recurringSerializer->serialize($rec)]);
    }

    #[Route('/recurring/{id}/delete', name: '_recurring_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteRecurring(int $id): JsonResponse
    {
        $rec = $this->loadOwnedRecurring($id);
        if (!$rec instanceof PersonalFinanceRecurringTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $rec->getWallet());

        $this->recurringManager->delete($rec);

        return $this->jsonSuccess();
    }

    // ----- Scheduled CRUD -----

    #[Route('/scheduled/create', name: '_scheduled_create', methods: [HttpMethodEnum::Post->value])]
    public function createScheduled(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->scheduledInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $sched = $this->scheduledManager->create($user, $input);
        } catch (DomainException $exception) {
            return $this->jsonInvalidInput(['walletId' => $exception->getMessage()]);
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $sched->getWallet());

        return $this->jsonSuccess(['scheduled' => $this->scheduledSerializer->serialize($sched)]);
    }

    #[Route('/scheduled/{id}/update', name: '_scheduled_update', methods: [HttpMethodEnum::Post->value])]
    public function updateScheduled(int $id, Request $request): JsonResponse
    {
        $sched = $this->loadOwnedScheduled($id);
        if (!$sched instanceof PersonalFinanceScheduledTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $sched->getWallet());

        $input = $this->scheduledInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->scheduledManager->update($sched, $input);
        } catch (DomainException $exception) {
            return $this->jsonInvalidInput(['walletId' => $exception->getMessage()]);
        }

        return $this->jsonSuccess(['scheduled' => $this->scheduledSerializer->serialize($sched)]);
    }

    #[Route('/scheduled/{id}/materialize', name: '_scheduled_materialize', methods: [HttpMethodEnum::Post->value])]
    public function materializeScheduled(int $id): JsonResponse
    {
        $sched = $this->loadOwnedScheduled($id);
        if (!$sched instanceof PersonalFinanceScheduledTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $sched->getWallet());

        try {
            $this->scheduledManager->materialize($sched);
        } catch (DomainException $exception) {
            return $this->jsonInvalidInput(['generated' => $exception->getMessage()]);
        }

        return $this->jsonSuccess(['scheduled' => $this->scheduledSerializer->serialize($sched)]);
    }

    #[Route('/scheduled/{id}/delete', name: '_scheduled_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteScheduled(int $id): JsonResponse
    {
        $sched = $this->loadOwnedScheduled($id);
        if (!$sched instanceof PersonalFinanceScheduledTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $sched->getWallet());

        $this->scheduledManager->delete($sched);

        return $this->jsonSuccess();
    }

    private function loadOwnedRecurring(int $id): ?PersonalFinanceRecurringTransactionInterface
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $rec = $this->recurringRepository->find($id);
        if (!$rec instanceof PersonalFinanceRecurringTransactionInterface) {
            return null;
        }
        if ($rec->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $rec;
    }

    private function loadOwnedScheduled(int $id): ?PersonalFinanceScheduledTransactionInterface
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $sched = $this->scheduledRepository->find($id);
        if (!$sched instanceof PersonalFinanceScheduledTransactionInterface) {
            return null;
        }
        if ($sched->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $sched;
    }
}
