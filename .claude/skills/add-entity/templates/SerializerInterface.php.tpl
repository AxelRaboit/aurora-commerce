<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Serializer;

use {{NAMESPACE}}\Entity\{{NAME}}Interface;

interface {{NAME}}SerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize({{NAME}}Interface ${{NAME_CAMEL}}): array;
}
