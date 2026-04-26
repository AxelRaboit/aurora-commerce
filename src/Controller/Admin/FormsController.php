<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\Form\FormManagerInterface;
use App\Controller\Trait\JsonRequestTrait;
use App\DTO\Form\FormFieldInput;
use App\DTO\Form\FormInput;
use App\DTO\PaginationRequest;
use App\Entity\Form;
use App\Entity\FormField;
use App\Enum\HttpMethodEnum;
use App\Enum\User\UserRoleEnum;
use App\Repository\Form\FormRepository;
use App\Repository\Form\FormSubmissionRepository;
use App\Serializer\FormSerializer;
use App\Service\Form\FormSubmissionExporter;
use App\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/forms', name: 'admin_forms')]
#[IsGranted(UserRoleEnum::Editor->value)]
final class FormsController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormSubmissionRepository $formSubmissionRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializer $formSerializer,
        private readonly FormSubmissionExporter $submissionExporter,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('admin/forms/index.html.twig', [
            'locales' => $this->getParameter('kernel.enabled_locales'),
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        $result = $this->formRepository->findPaginated($pagination->page, $pagination->limit);

        return $this->json([
            'ok' => true,
            'items' => array_map(fn (Form $form): array => $this->formSerializer->serialize($form, false), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/{id}', name: '_get', methods: [HttpMethodEnum::Get->value])]
    public function get(Form $form): JsonResponse
    {
        return $this->json(['ok' => true, 'form' => $this->formSerializer->serialize($form)]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = FormInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $form = $this->formManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['ok' => false, 'errors' => $this->mapManagerException($invalidArgumentException)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['ok' => true, 'form' => $this->formSerializer->serialize($form)], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Request $request, Form $form): JsonResponse
    {
        $input = FormInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->formManager->update($form, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['ok' => false, 'errors' => $this->mapManagerException($invalidArgumentException)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['ok' => true, 'form' => $this->formSerializer->serialize($form)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Form $form): JsonResponse
    {
        $this->formManager->delete($form);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/fields', name: '_field_create', methods: [HttpMethodEnum::Post->value])]
    public function createField(Request $request, Form $form): JsonResponse
    {
        $input = FormFieldInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $field = $this->formManager->createField($form, $input);

        return $this->json(['ok' => true, 'field' => $this->formSerializer->serializeField($field)], Response::HTTP_CREATED);
    }

    #[Route('/{id}/fields/{fieldId}/edit', name: '_field_update', methods: [HttpMethodEnum::Post->value])]
    public function updateField(Request $request, Form $form, int $fieldId): JsonResponse
    {
        $field = $form->findFieldById($fieldId);
        if (!$field instanceof FormField) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $input = FormFieldInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->formManager->updateField($field, $input);

        return $this->json(['ok' => true, 'field' => $this->formSerializer->serializeField($field)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteField(Form $form, int $fieldId): JsonResponse
    {
        $field = $form->findFieldById($fieldId);
        if (!$field instanceof FormField) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $this->formManager->deleteField($field);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/fields/reorder', name: '_field_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorderFields(Request $request, Form $form): JsonResponse
    {
        $data = $this->decodeJson($request);
        $rawIds = is_array($data['orderedIds'] ?? null) ? $data['orderedIds'] : [];
        $orderedIds = array_values(array_filter(
            array_map(intval(...), $rawIds),
            static fn (int $id): bool => $id > 0,
        ));

        $this->formManager->reorderFields($form, $orderedIds);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/submissions', name: '_submissions', methods: [HttpMethodEnum::Get->value])]
    public function submissions(PaginationRequest $pagination, Form $form): JsonResponse
    {
        $result = $this->formSubmissionRepository->findPaginatedByForm($form, $pagination->page, $pagination->limit);

        return $this->json([
            'ok' => true,
            'items' => array_map($this->formSerializer->serializeSubmission(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'fields' => array_values(array_map($this->formSerializer->serializeField(...), $form->getFields()->toArray())),
        ]);
    }

    #[Route('/{id}/submissions/export', name: '_submissions_export', methods: [HttpMethodEnum::Get->value])]
    public function exportSubmissions(Request $request, Form $form): StreamedResponse
    {
        $locale = (string) ($request->query->get('locale') ?: $request->getLocale());

        return $this->submissionExporter->exportToCsv($form, $locale);
    }

    /**
     * Maps an InvalidArgumentException from the manager to an error array.
     * Convention: message format is "field.path|Human message".
     *
     * @return array<string, string>
     */
    private function mapManagerException(InvalidArgumentException $e): array
    {
        $message = $e->getMessage();
        if (str_contains($message, '|')) {
            [$field, $humanMessage] = explode('|', $message, 2);

            return [$field => $humanMessage];
        }

        return ['_error' => $message];
    }
}
