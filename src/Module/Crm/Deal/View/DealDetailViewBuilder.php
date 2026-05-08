<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\View;

use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Serializer\DealSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin deal detail view. Centralises URL
 * generation + serialisation for the show screen.
 */
class DealDetailViewBuilder
{
    public function __construct(
        protected readonly DealSerializerInterface $dealSerializer,
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(DealInterface $deal): array
    {
        return [
            'deal' => $this->dealSerializer->serialize($deal),
            'backPath' => $this->urlGenerator->generate('backend_crm_deals'),
            'updatePath' => $this->urlGenerator->generate('backend_crm_deals_update', ['id' => $deal->getId()]),
            'deletePath' => $this->urlGenerator->generate('backend_crm_deals_delete', ['id' => $deal->getId()]),
            'updateStagePath' => $this->urlGenerator->generate('backend_crm_deals_stage', ['id' => $deal->getId()]),
        ];
    }
}
