<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Entity;

use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\Table(name: 'core_settings')]
class Setting extends AbstractSetting
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'setting_key', length: 100)]
        #[Groups(['setting:read'])]
        protected string $key = '',
        ?string $value = null,
        ?string $description = null,
        string $type = 'string',
        ?string $group = null,
    ) {
        $this->value = $value;
        $this->description = $description;
        $this->type = $type;
        $this->group = $group;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }
}
