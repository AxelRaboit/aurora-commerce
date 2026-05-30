<?php

return [
    Aurora\AuroraBundle::class => ['all' => true],
    // Monorepo-split: each extractable leaf module ships as its own
    // self-registering bundle (AbstractAuroraModuleBundle). Removing a line
    // removes that whole module — the "install only what you want" mechanism.
    // In the target topology each is a separate Composer package.
    Aurora\Module\Assistant\AuroraAssistantBundle::class => ['all' => true],
    Aurora\Module\Billing\AuroraBillingBundle::class => ['all' => true],
    Aurora\Module\Crm\AuroraCrmBundle::class => ['all' => true],
    // Ecommerce + Erp ship together as the aurora-commerce package (cat. E).
    Aurora\Module\Ecommerce\AuroraEcommerceBundle::class => ['all' => true],
    Aurora\Module\Erp\AuroraErpBundle::class => ['all' => true],
    Aurora\Module\Editorial\AuroraEditorialBundle::class => ['all' => true],
    Aurora\Module\Hr\AuroraHrBundle::class => ['all' => true],
    Aurora\Module\Notes\AuroraNotesBundle::class => ['all' => true],
    Aurora\Module\PersonalFinance\AuroraPersonalFinanceBundle::class => ['all' => true],
    Aurora\Module\Photo\AuroraPhotoBundle::class => ['all' => true],
    Aurora\Module\Planning\AuroraPlanningBundle::class => ['all' => true],
    Aurora\Module\Tools\AuroraToolsBundle::class => ['all' => true],
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Knp\DoctrineBehaviors\DoctrineBehaviorsBundle::class => ['all' => true],
    Pentatrion\ViteBundle\PentatrionViteBundle::class => ['all' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Symfony\UX\Vue\VueBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
];
