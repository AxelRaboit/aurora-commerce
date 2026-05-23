<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\PdfTemplateField\Dto\WeldingPdfTemplateFieldInputFactoryInterface;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;
use Aurora\Module\Welding\PdfTemplateField\Manager\WeldingPdfTemplateFieldManagerInterface;
use Aurora\Module\Welding\PdfTemplateField\Serializer\WeldingPdfTemplateFieldSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/pdf-template-fields', name: 'backend_welding_pdf_template_fields')]
#[IsGranted('welding.pdf_templates.edit')]
final class WeldingPdfTemplateFieldsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly WeldingPdfTemplateFieldManagerInterface $fieldManager,
        private readonly WeldingPdfTemplateFieldInputFactoryInterface $fieldInputFactory,
        private readonly WeldingPdfTemplateFieldSerializerInterface $fieldSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(WeldingPdfTemplateFieldInterface $field, Request $request): JsonResponse
    {
        $input = $this->fieldInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->fieldManager->update($field, $input);

        return $this->jsonSuccess(['field' => $this->fieldSerializer->serialize($field)]);
    }
}
