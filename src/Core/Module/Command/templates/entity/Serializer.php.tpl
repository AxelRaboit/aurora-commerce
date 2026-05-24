<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Serializer;

use {{NAMESPACE}}\Entity\{{NAME}}Interface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias({{NAME}}SerializerInterface::class)]
class {{NAME}}Serializer implements {{NAME}}SerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize({{NAME}}Interface ${{NAME_CAMEL}}): array
    {
        return [
            'id' => ${{NAME_CAMEL}}->getId(),
            'name' => ${{NAME_CAMEL}}->getName(),
            'createdAt' => ${{NAME_CAMEL}}->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
