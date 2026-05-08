<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Entity;

use Aurora\Core\Locale\Repository\LocaleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocaleRepository::class)]
#[ORM\Table(name: 'core_locales')]
class Locale extends AbstractLocale
{
    #[ORM\Id]
    #[ORM\Column(length: 10)]
    protected string $code;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }
}
