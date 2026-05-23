<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowTemplateInputFactoryInterface::class)]
class WorkflowTemplateInputFactory implements WorkflowTemplateInputFactoryInterface
{
    public function fromArray(array $data): WorkflowTemplateInputInterface
    {
        return new WorkflowTemplateInput(
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            applicableTo: Str::trimOrNullFromArray($data, 'applicableTo'),
        );
    }
}
