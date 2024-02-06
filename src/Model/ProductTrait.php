<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait ProductTrait
{
    /**
     * @var Collection<array-key, PriceTierInterface>
     *
     * @ORM\OneToMany(targetEntity="Setono\SyliusTierPricingPlugin\Model\PriceTierInterface", cascade={"all"}, mappedBy="product", orphanRemoval=true)
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

    public function addPriceTier(PriceTierInterface $priceTier): void
    {
        if (!$this->priceTiers->contains($priceTier)) {
            $this->priceTiers->add($priceTier);
            $priceTier->setProduct($this);
        }
    }

    public function removePriceTier(PriceTierInterface $priceTier): void
    {
        if ($this->priceTiers->contains($priceTier)) {
            $this->priceTiers->removeElement($priceTier);
            $priceTier->setProduct(null);
        }
    }
}
