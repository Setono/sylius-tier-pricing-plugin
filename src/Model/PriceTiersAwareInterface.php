<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Doctrine\Common\Collections\Collection;

interface PriceTiersAwareInterface
{
    /**
     * @return Collection<array-key, PriceTierInterface>
     */
    public function getPriceTiers(): Collection;

    public function addPriceTier(PriceTierInterface $priceTier): void;
}
