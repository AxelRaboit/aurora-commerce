<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Manager\OcrJobManagerInterface;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Aurora\Module\Billing\Ocr\Serializer\OcrJobSerializerInterface;
use Aurora\Module\Billing\Ocr\View\OcrJobsViewBuilder;
use Aurora\Module\Media\Library\Enum\MimeTypeEnum;
use Aurora\Module\Platform\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use function in_array;

#[Route('/backend/billing/ocr', name: 'backend_billing_ocr')]
#[IsGranted('billing.ocr.import')]
final class OcrImportController extends AbstractController
{
    use JsonResponseTrait;

    private const array ALLOWED_MIME_TYPES = [
        MimeTypeEnum::Jpeg->value,
        MimeTypeEnum::Png->value,
        MimeTypeEnum::Webp->value,
        MimeTypeEnum::Pdf->value,
    ];

    public function __construct(
        private readonly OcrJobManagerInterface $jobManager,
        private readonly OcrJobRepository $jobs,
        private readonly OcrJobSerializerInterface $jobSerializer,
        private readonly OcrJobsViewBuilder $jobsViewBuilder,
    ) {}

    #[Route('/import', name: '_import', methods: [HttpMethodEnum::Get->value])]
    public function import(): Response
    {
        $recent = $this->jobs->findRecent(10);

        return $this->render('@Billing/backend/ocr/import.html.twig', [
            'recentJobs' => array_map($this->jobSerializer->serialize(...), $recent),
            'uploadPath' => $this->generateUrl('backend_billing_ocr_import_upload'),
            'jobsPath' => $this->generateUrl('backend_billing_ocr_jobs'),
            'invoicesPath' => $this->generateUrl('backend_billing_invoices'),
            'invoiceShowPath' => $this->generateUrl('backend_billing_invoices_show', ['id' => '__id__']),
            'statusUrlTemplate' => $this->generateUrl('backend_billing_ocr_jobs_status', ['id' => '__id__']),
            'retryPath' => $this->generateUrl('backend_billing_ocr_jobs_retry', ['id' => '__id__']),
            'deletePath' => $this->generateUrl('backend_billing_ocr_jobs_delete', ['id' => '__id__']),
            'validatePathTemplate' => $this->generateUrl('backend_billing_invoices_validate', ['id' => '__id__']),
        ]);
    }

    #[Route('/import/upload', name: '_import_upload', methods: [HttpMethodEnum::Post->value])]
    public function upload(Request $request): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');
        if (null === $file) {
            return $this->jsonInvalidInput(['document' => 'backend.billing.ocr.upload.errors.missing']);
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return $this->jsonInvalidInput(['document' => 'backend.billing.ocr.upload.errors.unsupported']);
        }

        $user = $this->getUser();
        $job = $this->jobManager->createFromUpload($file, $user instanceof User ? $user : null);

        return $this->jsonSuccess(['job' => $this->jobSerializer->serialize($job)]);
    }

    #[Route('/jobs', name: '_jobs', methods: [HttpMethodEnum::Get->value])]
    public function jobs(Request $request): Response
    {
        $pagination = PaginationRequest::fromRequest($request);

        return $this->render('@Billing/backend/ocr/jobs.html.twig', $this->jobsViewBuilder->indexView($pagination, $request));
    }

    #[Route('/jobs/list', name: '_jobs_list', methods: [HttpMethodEnum::Get->value])]
    public function jobsList(Request $request): JsonResponse
    {
        $pagination = PaginationRequest::fromRequest($request);

        return $this->json($this->jobsViewBuilder->buildListPayload($pagination, $request));
    }

    #[Route('/jobs/{id}/status', name: '_jobs_status', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function status(OcrJob $job): JsonResponse
    {
        return $this->jsonSuccess(['job' => $this->jobSerializer->serialize($job)]);
    }

    #[Route('/jobs/{id}/retry', name: '_jobs_retry', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function retry(OcrJob $job): JsonResponse
    {
        $this->jobManager->retry($job);

        return $this->jsonSuccess(['job' => $this->jobSerializer->serialize($job)]);
    }

    #[Route('/jobs/{id}/delete', name: '_jobs_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function deleteJob(OcrJob $job, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $deleteTiers = (bool) ($body['deleteTiers'] ?? false);

        try {
            $this->jobManager->delete($job, $deleteTiers);
        } catch (Throwable $throwable) {
            return $this->jsonFailure('backend.billing.ocr.deleteError', extra: ['detail' => $throwable->getMessage()]);
        }

        return $this->jsonSuccess();
    }
}
