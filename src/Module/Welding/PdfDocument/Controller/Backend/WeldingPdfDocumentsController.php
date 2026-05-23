<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\PdfDocument\Dto\WeldingPdfDocumentInputFactoryInterface;
use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocumentInterface;
use Aurora\Module\Welding\PdfDocument\Manager\WeldingPdfDocumentManagerInterface;
use Aurora\Module\Welding\PdfDocument\Serializer\WeldingPdfDocumentSerializerInterface;
use Aurora\Module\Welding\PdfDocument\View\WeldingPdfDocumentsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/pdf-documents', name: 'backend_welding_pdf_documents')]
#[IsGranted('welding.pdf_documents.view')]
final class WeldingPdfDocumentsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly WeldingPdfDocumentSerializerInterface $serializer,
        private readonly WeldingPdfDocumentManagerInterface $manager,
        private readonly PayloadValidator $payloadValidator,
        private readonly WeldingPdfDocumentsViewBuilder $viewBuilder,
        private readonly WeldingPdfDocumentInputFactoryInterface $inputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Welding/backend/pdf_documents/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination));
    }

    #[Route('/generate', name: '_generate', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.pdf_documents.generate')]
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
    public function download(WeldingPdfDocumentInterface $document): Response
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
    #[IsGranted('welding.pdf_documents.delete')]
    public function delete(WeldingPdfDocumentInterface $document): JsonResponse
    {
        $this->manager->delete($document);

        return $this->jsonSuccess();
    }
}
