<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Dev\Audit\Entity\AuditLog;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AuditLogTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new AuditLog('crm', 'create'))->getId());
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $before = new DateTimeImmutable();
        $log = new AuditLog('crm', 'create');
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $log->getCreatedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $log->getCreatedAt()->getTimestamp());
    }

    public function testRequiredFieldsAccessibleViaGetters(): void
    {
        $log = new AuditLog('billing', 'invoice.paid');

        self::assertSame('billing', $log->getModule());
        self::assertSame('invoice.paid', $log->getAction());
    }

    public function testOptionalFieldsNullByDefault(): void
    {
        $log = new AuditLog('crm', 'create');

        self::assertNull($log->getReference());
        self::assertNull($log->getEntityType());
        self::assertNull($log->getEntityId());
        self::assertNull($log->getUserId());
        self::assertNull($log->getUserEmail());
        self::assertNull($log->getUserName());
        self::assertNull($log->getData());
    }

    public function testOptionalFieldsAccessibleViaConstructor(): void
    {
        $log = new AuditLog(
            module: 'crm',
            action: 'contact.update',
            entityType: 'Contact',
            entityId: 42,
            userId: 7,
            userEmail: 'admin@example.com',
            userName: 'Admin',
            data: ['before' => 'A', 'after' => 'B'],
        );

        self::assertSame('Contact', $log->getEntityType());
        self::assertSame(42, $log->getEntityId());
        self::assertSame(7, $log->getUserId());
        self::assertSame('admin@example.com', $log->getUserEmail());
        self::assertSame('Admin', $log->getUserName());
        self::assertSame(['before' => 'A', 'after' => 'B'], $log->getData());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $log = new AuditLog('crm', 'create');

        $log->setReference('AUD-001');
        self::assertSame('AUD-001', $log->getReference());

        self::assertSame($log, $log->setReference(null));
        self::assertNull($log->getReference());
    }
}
