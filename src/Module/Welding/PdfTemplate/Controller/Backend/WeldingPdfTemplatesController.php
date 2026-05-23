<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\PdfTemplate\Dto\WeldingPdfTemplateInputFactoryInterface;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\PdfTemplate\Manager\WeldingPdfTemplateManagerInterface;
use Aurora\Module\Welding\PdfTemplate\Serializer\WeldingPdfTemplateSerializerInterface;
use Aurora\Module\Welding\PdfTemplate\View\WeldingPdfTemplatesViewBuilder;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/pdf-templates', name: 'backend_welding_pdf_templates')]
#[IsGranted('welding.pdf_templates.view')]
final class WeldingPdfTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly WeldingPdfTemplateSerializerInterface $serializer,
        private readonly WeldingPdfTemplateManagerInterface $manager,
        private readonly PayloadValidator $payloadValidator,
        private readonly WeldingPdfTemplatesViewBuilder $viewBuilder,
        private readonly WeldingPdfTemplateInputFactoryInterface $inputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Welding/backend/pdf_templates/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        $statusFilter = $request->query->getString('status') ?: null;

        return $this->json($this->viewBuilder->buildListPayload($pagination, $statusFilter));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.pdf_templates.create')]
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
    #[IsGranted('welding.pdf_templates.edit')]
    public function update(WeldingPdfTemplateInterface $template, Request $request): JsonResponse
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
    #[IsGranted('welding.pdf_templates.delete')]
    public function delete(WeldingPdfTemplateInterface $template): JsonResponse
    {
        $this->manager->delete($template);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/detect-fields', name: '_detect_fields', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.pdf_templates.edit')]
    public function detectFields(WeldingPdfTemplateInterface $template): JsonResponse
    {
        try {
            $fields = $this->manager->detectAndSyncFields($template);
        } catch (RuntimeException $runtimeException) {
            return $this->jsonFailure($runtimeException->getMessage(), HttpStatusEnum::ServiceUnavailable->value);
        }

        return $this->jsonSuccess([
            'fields' => $fields,
            'template' => $this->serializer->serialize($template),
        ]);
    }
}
