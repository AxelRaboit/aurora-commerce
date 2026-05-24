<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Entity;

use {{NAMESPACE}}\Repository\{{NAME}}Repository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: {{NAME}}Repository::class)]
#[ORM\Table(name: '{{TABLE_NAME}}')]
class {{NAME}} extends Abstract{{NAME}}
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: '{{SEQUENCE_NAME}}', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
