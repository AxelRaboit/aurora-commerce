<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\MappedSuperclass]
abstract class AbstractSetting implements SettingInterface
{
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['setting:read'])]
    protected ?string $value = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['setting:read'])]
    protected ?string $description = null;

    #[ORM\Column(name: 'setting_type', length: 50)]
    #[Groups(['setting:read'])]
    protected string $type = 'string';

    #[ORM\Column(name: 'setting_group', length: 100, nullable: true)]
    #[Groups(['setting:read'])]
    protected ?string $group = null;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getCastedValue(): mixed
    {
        return match ($this->type) {
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $this->value,
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
