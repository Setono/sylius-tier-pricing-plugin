<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Provider;

use Setono\SyliusTierPricingPlugin\Model\PriceTierInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

abstract class AbstractPriceTierProvider implements PriceTierProviderInterface
{
    public function getPriceTier(
        int $quantity,
        ProductVariantInterface $productVariant,
        ChannelInterface $channel = null,
    ): ?PriceTierInterface {
        $resolvedPriceTier = null;

        foreach ($this->getPriceTiers($productVariant, $channel) as $priceTier) {
            if ($quantity < $priceTier->getQuantity()) {
                continue;
            }

            if (null === $resolvedPriceTier) {
                $resolvedPriceTier = $priceTier;
            }

            if ($priceTier->getQuantity() > $resolvedPriceTier->getQuantity()) {
                $resolvedPriceTier = $priceTier;
            }
        }

        return $resolvedPriceTier;
    }
}
