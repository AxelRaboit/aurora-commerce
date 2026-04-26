<?php

declare(strict_types=1);

namespace App\Core\Validation\ArgumentResolver;

use App\Core\Validation\DTO\PaginationRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class PaginationRequestResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (PaginationRequest::class !== $argument->getType()) {
            return [];
        }

        yield PaginationRequest::fromRequest($request);
    }
}
