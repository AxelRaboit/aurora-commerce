<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\User\Dto;

use Aurora\Core\User\Dto\UserSetPasswordInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class UserSetPasswordInputTest extends TestCase
{
    public function testConstructorAssignsValues(): void
    {
        $input = new UserSetPasswordInput('secret', 'secret');

        self::assertSame('secret', $input->password);
        self::assertSame('secret', $input->passwordConfirm);
    }

    public function testValidateMatchAddsViolationOnMismatch(): void
    {
        $input = new UserSetPasswordInput('a', 'b');

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('atPath')->with('passwordConfirm')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())
            ->method('buildViolation')
            ->with('auth.invitation.errors.password_mismatch')
            ->willReturn($builder);

        $input->validateMatch($context);
    }

    public function testValidateMatchSkipsWhenPasswordsMatch(): void
    {
        $input = new UserSetPasswordInput('same', 'same');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        $input->validateMatch($context);
    }
}
