<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PdfForm\PdfTemplateField\Dto\PdfTemplateFieldInputFactoryInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Manager\PdfTemplateFieldManagerInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Serializer\PdfTemplateFieldSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/pdfform/template-fields', name: 'backend_pdfform_template_fields')]
#[IsGranted('pdfform.templates.edit')]
final class PdfTemplateFieldsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PdfTemplateFieldManagerInterface $fieldManager,
        private readonly PdfTemplateFieldInputFactoryInterface $fieldInputFactory,
        private readonly PdfTemplateFieldSerializerInterface $fieldSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(PdfTemplateFieldInterface $field, Request $request): JsonResponse
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
