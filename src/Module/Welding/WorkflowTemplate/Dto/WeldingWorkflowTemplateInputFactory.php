<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowTemplateInputFactoryInterface::class)]
class WeldingWorkflowTemplateInputFactory implements WeldingWorkflowTemplateInputFactoryInterface
{
    public function fromArray(array $data): WeldingWorkflowTemplateInputInterface
    {
        return new WeldingWorkflowTemplateInput(
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            applicableTo: Str::trimOrNullFromArray($data, 'applicableTo'),
        );
    }
}
