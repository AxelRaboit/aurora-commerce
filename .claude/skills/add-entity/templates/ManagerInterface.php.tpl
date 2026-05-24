<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Manager;

use {{NAMESPACE}}\Dto\{{NAME}}InputInterface;
use {{NAMESPACE}}\Entity\{{NAME}}Interface;

interface {{NAME}}ManagerInterface
{
    public function create({{NAME}}InputInterface $input): {{NAME}}Interface;

    public function update({{NAME}}Interface ${{NAME_CAMEL}}, {{NAME}}InputInterface $input): void;

    public function delete({{NAME}}Interface ${{NAME_CAMEL}}): void;
}
