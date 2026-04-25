<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\FormManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\FormFieldInput;
use App\DTO\FormInput;
use App\DTO\PaginationRequest;
use App\Entity\Form;
use App\Entity\FormField;
use App\Entity\FormFieldTranslation;
use App\Entity\FormTranslation;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\FormRepository;
use App\Repository\FormSubmissionRepository;
use App\Serializer\FormSerializer;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/forms', name: 'admin_forms')]
#[IsGranted(UserRoleEnum::Editor->value)]
final class FormsController extends AbstractController
{
    use JsonValidationTrait;

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormSubmissionRepository $formSubmissionRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializer $formSerializer,
        private readonly ValidatorInterface $validator,
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
        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
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
        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
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
        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $field = $this->formManager->createField($form, $input);

        return $this->json(['ok' => true, 'field' => $this->formSerializer->serializeField($field)], Response::HTTP_CREATED);
    }

    #[Route('/{id}/fields/{fieldId}/edit', name: '_field_update', methods: [HttpMethodEnum::Post->value])]
    public function updateField(Request $request, Form $form, int $fieldId): JsonResponse
    {
        $field = $this->findField($form, $fieldId);
        if (!$field instanceof FormField) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $input = FormFieldInput::fromArray($this->decodeJson($request));
        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->formManager->updateField($field, $input);

        return $this->json(['ok' => true, 'field' => $this->formSerializer->serializeField($field)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteField(Form $form, int $fieldId): JsonResponse
    {
        $field = $this->findField($form, $fieldId);
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
        $submissions = $this->formSubmissionRepository->findAllByForm($form);
        $fields = $form->getFields()->toArray();

        $labels = array_map(static function (FormField $field) use ($locale): string {
            $translation = $field->getTranslation($locale);
            if (!$translation instanceof FormFieldTranslation) {
                $first = $field->getTranslations()->first();
                $translation = $first instanceof FormFieldTranslation ? $first : null;
            }

            return $translation?->getLabel() ?? '#'.$field->getId();
        }, $fields);

        $response = new StreamedResponse(static function () use ($submissions, $labels, $fields): void {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_merge(['ID', 'Date', 'Locale', 'IP'], $labels), ';');
            foreach ($submissions as $submission) {
                $row = [(string) $submission->getId(), $submission->getSubmittedAt()->format('d/m/Y H:i:s'), $submission->getLocale(), (string) $submission->getIp()];
                foreach ($fields as $field) {
                    $value = $submission->getData()[(string) $field->getId()] ?? '';
                    $row[] = is_array($value) ? implode(', ', $value) : (string) $value;
                }

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        });

        $formTranslation = $form->getTranslation($locale);
        if (!$formTranslation instanceof FormTranslation) {
            $first = $form->getTranslations()->first();
            $formTranslation = $first instanceof FormTranslation ? $first : null;
        }

        $slug = $formTranslation?->getSlug() ?? (string) $form->getId();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="soumissions-%s-%s.csv"', $slug, date('Ymd')));

        return $response;
    }

    private function findField(Form $form, int $fieldId): ?FormField
    {
        foreach ($form->getFields() as $field) {
            if ($field->getId() === $fieldId) {
                return $field;
            }
        }

        return null;
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
