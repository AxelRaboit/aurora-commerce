<?php

declare(strict_types=1);

namespace Aurora\Core\Http;

use Aurora\Core\Enum\HttpStatusEnum;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Standardised JSON envelope helpers for controller actions.
 *
 * Wire format:
 *   - Success: { success: true, ...data }
 *   - Failure: { success: false, error: <code>, ...extra }
 *   - Validation: { success: false, errors: { field: <key> } }
 *
 * For universal error codes (not_found, forbidden, conflict…) prefer the
 * `JsonErrorCode` enum to gain type safety and avoid typos. Domain-specific
 * codes (`finalized`, `identity_required`, …) stay as raw strings since they
 * belong to their feature and would pollute a shared enum.
 */
trait JsonResponseTrait
{
    /**
     * @param array<string, mixed> $data
     */
    private function jsonSuccess(array $data = [], int $status = HttpStatusEnum::Ok->value): JsonResponse
    {
        return $this->json(['success' => true, ...$data], $status);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function jsonFailure(string|JsonErrorCode $code, int $status = HttpStatusEnum::BadRequest->value, array $extra = []): JsonResponse
    {
        return $this->json([
            'success' => false,
            'error' => $code instanceof JsonErrorCode ? $code->value : $code,
            ...$extra,
        ], $status);
    }

    /**
     * @param array<string, mixed> $errors field => translation key (or message)
     */
    private function jsonInvalidInput(array $errors, int $status = HttpStatusEnum::UnprocessableEntity->value): JsonResponse
    {
        return $this->json(['success' => false, 'errors' => $errors], $status);
    }

    private function jsonNotFound(): JsonResponse
    {
        return $this->jsonFailure(JsonErrorCode::NotFound, HttpStatusEnum::NotFound->value);
    }

    private function jsonForbidden(): JsonResponse
    {
        return $this->jsonFailure(JsonErrorCode::Forbidden, HttpStatusEnum::Forbidden->value);
    }
}
