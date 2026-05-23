<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Dto;

/**
 * Welder submits a step they've filled. Marker DTO — no payload fields in V1
 * (PDF fill values live on PdfDocument, not on the step itself).
 */
interface WorkflowStepSubmitInputInterface
{
}
