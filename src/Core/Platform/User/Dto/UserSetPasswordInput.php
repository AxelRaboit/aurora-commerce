<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\User\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class UserSetPasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'auth.invitation.errors.password_required')]
        #[Assert\Length(min: 8, minMessage: 'auth.invitation.errors.password_too_short')]
        public string $password,
        #[Assert\NotBlank(message: 'auth.invitation.errors.password_confirm_required')]
        public string $passwordConfirm,
    ) {}

    #[Assert\Callback]
    public function validateMatch(ExecutionContextInterface $context): void
    {
        if ($this->password !== $this->passwordConfirm) {
            $context->buildViolation('auth.invitation.errors.password_mismatch')
                ->atPath('passwordConfirm')
                ->addViolation();
        }
    }
}
