<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Company\View;

use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Company\Serializer\CompanySerializerInterface;
use Aurora\Module\Crm\Company\View\CompanyDetailViewBuilder;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CompanyDetailViewBuilderTest extends TestCase
{
    public function testShowViewReturnsCompanyAndContacts(): void
    {
        $company = $this->createStub(CompanyInterface::class);
        $company->method('getId')->willReturn(5);

        $companySerializer = $this->createStub(CompanySerializerInterface::class);
        $companySerializer->method('serialize')->willReturn(['id' => 5]);

        $contactRepo = $this->createStub(ContactRepository::class);
        $contactRepo->method('findPaginated')->willReturn([
            'items' => [],
            'total' => 0,
            'page' => 1,
            'totalPages' => 0,
        ]);

        $contactSerializer = $this->createStub(ContactSerializerInterface::class);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $view = (new CompanyDetailViewBuilder($companySerializer, $contactRepo, $contactSerializer, $urlGenerator))->showView($company);

        self::assertSame(['id' => 5], $view['company']);
        self::assertArrayHasKey('contacts', $view);
        self::assertArrayHasKey('backPath', $view);
        self::assertArrayHasKey('createContactPath', $view);
    }
}
