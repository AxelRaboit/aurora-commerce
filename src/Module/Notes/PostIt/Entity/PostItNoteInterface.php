<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PostItNoteInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getAgency(): ?AgencyInterface;

    public function setAgency(?AgencyInterface $agency): static;

    public function getTitle(): ?string;

    public function setTitle(?string $title): static;

    public function getContent(): ?string;

    public function setContent(?string $content): static;

    public function getColor(): string;

    public function setColor(string $color): static;

    public function getPositionX(): int;

    public function setPositionX(int $positionX): static;

    public function getPositionY(): int;

    public function setPositionY(int $positionY): static;
}
