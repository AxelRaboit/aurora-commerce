<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PdfForm\PdfTemplate\Dto\PdfTemplateInputFactoryInterface;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\PdfForm\PdfTemplate\Manager\PdfTemplateManagerInterface;
use Aurora\Module\PdfForm\PdfTemplate\Serializer\PdfTemplateSerializerInterface;
use Aurora\Module\PdfForm\PdfTemplate\View\PdfTemplatesViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/pdfform/templates', name: 'backend_pdfform_templates')]
#[IsGranted('pdfform.templates.manage')]
final class PdfTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PdfTemplateSerializerInterface $serializer,
        private readonly PdfTemplateManagerInterface $manager,
        private readonly PayloadValidator $payloadValidator,
        private readonly PdfTemplatesViewBuilder $viewBuilder,
        private readonly PdfTemplateInputFactoryInterface $inputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@PdfForm/backend/templates/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        $statusFilter = $request->query->getString('status') ?: null;

        return $this->json($this->viewBuilder->buildListPayload($pagination, $statusFilter));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $template = $this->manager->create($input);

        return $this->jsonSuccess(['template' => $this->serializer->serialize($template)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(PdfTemplateInterface $template, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($template, $input);

        return $this->jsonSuccess(['template' => $this->serializer->serialize($template)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('pdfform.templates.delete')]
    public function delete(PdfTemplateInterface $template): JsonResponse
    {
        $this->manager->delete($template);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/detect-fields', name: '_detect_fields', methods: [HttpMethodEnum::Post->value])]
    public function detectFields(PdfTemplateInterface $template): JsonResponse
    {
        try {
            $fields = $this->manager->detectAndSyncFields($template);
        } catch (\RuntimeException $exception) {
            return $this->jsonFailure($exception->getMessage(), HttpStatusEnum::ServiceUnavailable->value);
        }

        return $this->jsonSuccess([
            'fields' => $fields,
            'template' => $this->serializer->serialize($template),
        ]);
    }
}
