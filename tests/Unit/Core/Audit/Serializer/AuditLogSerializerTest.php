<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Audit\Serializer;

use Aurora\Core\Audit\Entity\AuditLogInterface;
use Aurora\Core\Audit\Serializer\AuditLogSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AuditLogSerializerTest extends TestCase
{
    private function makeLog(
        int $id = 1,
        string $module = 'crm',
        string $action = 'contact.create',
        ?string $entityType = 'Contact',
        ?int $entityId = 42,
        ?int $userId = 7,
        ?string $userEmail = 'admin@example.com',
        ?string $userName = 'Admin',
        ?array $data = null,
        string $createdAt = '2026-01-15T10:00:00+00:00',
    ): AuditLogInterface {
        $log = $this->createStub(AuditLogInterface::class);
        $log->method('getId')->willReturn($id);
        $log->method('getModule')->willReturn($module);
        $log->method('getAction')->willReturn($action);
        $log->method('getEntityType')->willReturn($entityType);
        $log->method('getEntityId')->willReturn($entityId);
        $log->method('getUserId')->willReturn($userId);
        $log->method('getUserEmail')->willReturn($userEmail);
        $log->method('getUserName')->willReturn($userName);
        $log->method('getData')->willReturn($data);
        $log->method('getCreatedAt')->willReturn(new DateTimeImmutable($createdAt));

        return $log;
    }

    public function testSerializeReturnsAllExpectedFields(): void
    {
        $result = (new AuditLogSerializer())->serialize($this->makeLog());

        self::assertSame(1, $result['id']);
        self::assertSame('crm', $result['module']);
        self::assertSame('contact.create', $result['action']);
        self::assertSame('Contact', $result['entityType']);
        self::assertSame(42, $result['entityId']);
        self::assertSame(7, $result['userId']);
        self::assertSame('admin@example.com', $result['userEmail']);
        self::assertSame('Admin', $result['userName']);
    }

    public function testSerializeFormatsCreatedAtInAtom(): void
    {
        $result = (new AuditLogSerializer())->serialize($this->makeLog());

        self::assertSame('2026-01-15T10:00:00+00:00', $result['createdAt']);
    }

    public function testSerializePreservesNullOptionalFields(): void
    {
        $log = $this->makeLog(
            entityType: null,
            entityId: null,
            userId: null,
            userEmail: null,
            userName: null,
            data: null,
        );

        $result = (new AuditLogSerializer())->serialize($log);

        self::assertNull($result['entityType']);
        self::assertNull($result['entityId']);
        self::assertNull($result['userId']);
        self::assertNull($result['userEmail']);
        self::assertNull($result['userName']);
        self::assertNull($result['data']);
    }

    public function testSerializeIncludesDataPayloadWhenSet(): void
    {
        $log = $this->makeLog(data: ['before' => 'A', 'after' => 'B']);

        $result = (new AuditLogSerializer())->serialize($log);

        self::assertSame(['before' => 'A', 'after' => 'B'], $result['data']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $result = (new AuditLogSerializer())->serialize($this->makeLog());

        self::assertSame(
            ['id', 'module', 'action', 'entityType', 'entityId', 'userId', 'userEmail', 'userName', 'data', 'createdAt'],
            array_keys($result),
        );
    }
}
