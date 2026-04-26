<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Entity\User;
use App\Repository\User\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $existing = $this->userRepository->findOneBy(['email' => (string) $value]);

        if (!$existing instanceof User) {
            return;
        }

        if ($constraint->excludeSelf) {
            $currentUser = $this->security->getUser();
            if ($currentUser instanceof User && $existing->getId() === $currentUser->getId()) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
