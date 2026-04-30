<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
    private function jsonSuccess(array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json(['success' => true, ...$data], $status);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function jsonFailure(string|JsonErrorCode $code, int $status = Response::HTTP_BAD_REQUEST, array $extra = []): JsonResponse
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
    private function jsonInvalidInput(array $errors, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return $this->json(['success' => false, 'errors' => $errors], $status);
    }

    private function jsonNotFound(): JsonResponse
    {
        return $this->jsonFailure(JsonErrorCode::NotFound, Response::HTTP_NOT_FOUND);
    }

    private function jsonForbidden(): JsonResponse
    {
        return $this->jsonFailure(JsonErrorCode::Forbidden, Response::HTTP_FORBIDDEN);
    }
}
