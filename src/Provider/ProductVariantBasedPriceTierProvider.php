<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Provider;

use Setono\SyliusTierPricingPlugin\Model\PriceTiersAwareInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class ProductVariantBasedPriceTierProvider extends AbstractPriceTierProvider
{
    public function getPriceTiers(ProductVariantInterface $productVariant, ChannelInterface $channel = null): iterable
    {
        Assert::isInstanceOf($productVariant, PriceTiersAwareInterface::class);

        return $productVariant->getPriceTiers();
    }
}
