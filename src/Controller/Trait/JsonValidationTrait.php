<?php

declare(strict_types=1);

namespace App\Controller\Trait;

use Symfony\Component\Validator\ConstraintViolationListInterface;

trait JsonValidationTrait
{
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            if (!isset($errors[$field])) {
                $errors[$field] = $violation->getMessage();
            }
        }

        return $errors;
    }
}
