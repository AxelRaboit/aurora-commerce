<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Entity;

interface ListingCategoryTranslationInterface
{
    public function getId(): ?int;

    public function getLocale(): string;

    public function setLocale(string $locale): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getSlug(): string;

    public function setSlug(string $slug): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getSeoTitle(): ?string;

    public function setSeoTitle(?string $seoTitle): static;

    public function getSeoDescription(): ?string;

    public function setSeoDescription(?string $seoDescription): static;

    public function getCategory(): ListingCategoryInterface;

    public function setCategory(ListingCategoryInterface $category): static;
}
