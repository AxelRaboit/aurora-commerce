<?php

declare(strict_types=1);

namespace Aurora\Core\User\DTO;

interface UserInputInterface
{
    public function getName(): string;

    public function getEmail(): string;

    public function getRole(): string;

    public function getLocale(): string;

    public function getPassword(): ?string;

    public function getManagerId(): ?int;

    public function getAgencyId(): ?int;

    public function getServiceId(): ?int;
}
