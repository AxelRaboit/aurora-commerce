<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PdfForm\PdfDocument\Dto\PdfDocumentInputFactoryInterface;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;
use Aurora\Module\PdfForm\PdfDocument\Manager\PdfDocumentManagerInterface;
use Aurora\Module\PdfForm\PdfDocument\Serializer\PdfDocumentSerializerInterface;
use Aurora\Module\PdfForm\PdfDocument\View\PdfDocumentsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/pdfform/documents', name: 'backend_pdfform_documents')]
#[IsGranted('pdfform.documents.view')]
final class PdfDocumentsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PdfDocumentSerializerInterface $serializer,
        private readonly PdfDocumentManagerInterface $manager,
        private readonly PayloadValidator $payloadValidator,
        private readonly PdfDocumentsViewBuilder $viewBuilder,
        private readonly PdfDocumentInputFactoryInterface $inputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@PdfForm/backend/documents/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination));
    }

    #[Route('/generate', name: '_generate', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('pdfform.documents.generate')]
    public function generate(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $document = $this->manager->generate($input);

        return $this->jsonSuccess(['document' => $this->serializer->serialize($document)]);
    }

    #[Route('/{id}/download', name: '_download', methods: [HttpMethodEnum::Get->value])]
    public function download(PdfDocumentInterface $document): Response
    {
        $path = $this->manager->getAbsolutePath($document);

        if (null === $path || !file_exists($path)) {
            return $this->jsonFailure('file_not_found', HttpStatusEnum::NotFound->value);
        }

        $filename = ($document->getLabel() ?? $document->getReference() ?? 'document').'.pdf';
        $size = filesize($path);

        $response = new StreamedResponse(static function () use ($path): void {
            readfile($path);
        });

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="'.$filename.'"');
        $response->headers->set('Content-Length', (string) $size);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('pdfform.documents.delete')]
    public function delete(PdfDocumentInterface $document): JsonResponse
    {
        $this->manager->delete($document);

        return $this->jsonSuccess();
    }
}
