<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface as BaseProductInterface;

interface ProductInterface extends BaseProductInterface
{
    /**
     * @return Collection<array-key, PriceTierInterface>
     */
    public function getPriceTiers(): Collection;

    public function addPriceTier(PriceTierInterface $priceTier): void;

    public function removePriceTier(PriceTierInterface $priceTier): void;
}
