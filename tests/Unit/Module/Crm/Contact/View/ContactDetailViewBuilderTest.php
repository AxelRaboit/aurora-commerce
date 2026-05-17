<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Contact\View;

use Aurora\Core\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Core\Dev\Audit\Serializer\AuditLogSerializer;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializerInterface;
use Aurora\Module\Crm\Contact\View\ContactDetailViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ContactDetailViewBuilderTest extends TestCase
{
    public function testShowViewReturnsContactAndPaths(): void
    {
        $contact = $this->createStub(ContactInterface::class);
        $contact->method('getId')->willReturn(7);

        $contactSerializer = $this->createStub(ContactSerializerInterface::class);
        $contactSerializer->method('serialize')->willReturn(['id' => 7]);

        $auditRepo = $this->createStub(AuditLogRepository::class);
        $auditRepo->method('findPaginatedForEntity')->willReturn([
            'items' => [],
            'total' => 0,
            'page' => 1,
            'totalPages' => 0,
        ]);

        $auditSerializer = $this->createStub(AuditLogSerializer::class);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $view = (new ContactDetailViewBuilder($contactSerializer, $auditRepo, $auditSerializer, $urlGenerator))->showView($contact);

        self::assertSame(['id' => 7], $view['contact']);
        self::assertArrayHasKey('activity', $view);
        self::assertArrayHasKey('backPath', $view);
        self::assertArrayHasKey('updatePath', $view);
    }
}
