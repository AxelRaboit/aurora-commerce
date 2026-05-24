<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Planning\Event\Dto\PlanningEventInputFactoryInterface;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Event\Manager\PlanningEventManagerInterface;
use Aurora\Module\Planning\Event\Repository\PlanningEventRepository;
use Aurora\Module\Planning\Event\Serializer\PlanningEventSerializerInterface;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/backend/plannings', name: 'backend_plannings')]
#[IsGranted('planning.plannings.view')]
class PlanningEventsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly PlanningEventRepository $eventRepository,
        protected readonly PlanningEventSerializerInterface $eventSerializer,
        protected readonly PlanningEventManagerInterface $eventManager,
        protected readonly PlanningEventInputFactoryInterface $eventInputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/events', name: '_events_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PlanningInterface $planning, Request $request): JsonResponse
    {
        $from = $this->parseDate($request->query->get('from'));
        $to = $this->parseDate($request->query->get('to'));

        $events = $this->eventRepository->findInRange($planning, $from, $to);

        return $this->jsonSuccess([
            'items' => array_map($this->eventSerializer->serialize(...), $events),
        ]);
    }

    #[Route('/{id}/events', name: '_events_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('planning.events.create')]
    public function create(PlanningInterface $planning, Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);
        $payload['planningId'] = $planning->getId();

        $input = $this->eventInputFactory->fromArray($payload);
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $event = $this->eventManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['planningId' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['event' => $this->eventSerializer->serialize($event)]);
    }

    #[Route('/events/{eventId}/edit', name: '_events_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('planning.events.edit')]
    public function update(int $eventId, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event instanceof PlanningEventInterface) {
            return $this->jsonNotFound();
        }

        if (null !== $event->getSourceType()) {
            return $this->jsonInvalidInput(['_global' => 'backend.planning_events.errors.source_locked']);
        }

        $payload = $this->decodeJson($request);
        $payload['planningId'] = $event->getPlanning()->getId();

        $input = $this->eventInputFactory->fromArray($payload);
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->eventManager->update($event, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['planningId' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['event' => $this->eventSerializer->serialize($event)]);
    }

    #[Route('/events/{eventId}/delete', name: '_events_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('planning.events.delete')]
    public function delete(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event instanceof PlanningEventInterface) {
            return $this->jsonNotFound();
        }

        if (null !== $event->getSourceType()) {
            return $this->jsonInvalidInput(['_global' => 'backend.planning_events.errors.source_locked']);
        }

        $this->eventManager->delete($event);

        return $this->jsonSuccess();
    }

    private function parseDate(mixed $value): DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return new DateTimeImmutable();
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return new DateTimeImmutable();
        }
    }
}
