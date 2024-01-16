<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

trait PriceTiersAwareTrait
{
    /**
     * @var Collection<array-key, PriceTierInterface>
     */
    protected Collection $priceTiers;

    public function __construct()
    {
        $this->priceTiers = new ArrayCollection();
    }

    /**
     * @return Collection<array-key, PriceTierInterface>
     */
    public function getPriceTiers(): Collection
    {
        return $this->priceTiers;
    }
}
