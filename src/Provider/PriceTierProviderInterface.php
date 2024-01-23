<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Provider;

use Setono\SyliusTierPricingPlugin\Model\PriceTierInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;

interface PriceTierProviderInterface
{
    /**
     * Returns the price tier for the given quantity, product variant and channel or null if none is found
     */
    public function getPriceTier(
        int $quantity,
        ProductVariantInterface $productVariant,
        ChannelInterface $channel = null,
    ): ?PriceTierInterface;

    /**
     * Returns all available price tiers for the given product variant and channel
     *
     * @return list<PriceTierInterface>
     */
    public function getPriceTiers(
        ProductVariantInterface $productVariant,
        ChannelInterface $channel = null,
    ): array;
}
