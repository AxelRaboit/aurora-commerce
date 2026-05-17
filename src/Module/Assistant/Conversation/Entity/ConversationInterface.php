<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Entity;

use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Collection;

interface ConversationInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getAgency(): ?AgencyInterface;

    public function setAgency(?AgencyInterface $agency): static;

    public function getTitle(): ?string;

    public function setTitle(?string $title): static;

    public function getModel(): ?string;

    public function setModel(?string $model): static;

    /** @return Collection<int, MessageInterface> */
    public function getMessages(): Collection;

    public function addMessage(MessageInterface $message): static;
}
