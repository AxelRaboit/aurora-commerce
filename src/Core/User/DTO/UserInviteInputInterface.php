<?php

declare(strict_types=1);

namespace Aurora\Core\User\DTO;

interface UserInviteInputInterface
{
    public function getName(): string;

    public function getEmail(): string;

    public function getRole(): string;

    public function getMessage(): ?string;
}
