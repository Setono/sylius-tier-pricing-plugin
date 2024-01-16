<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Provider;

use Setono\SyliusTierPricingPlugin\Model\PriceTiersAwareInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class ProductBasedPriceTierProvider extends AbstractPriceTierProvider
{
    public function getPriceTiers(ProductVariantInterface $productVariant, ChannelInterface $channel = null): iterable
    {
        /** @var PriceTiersAwareInterface|ProductInterface|null $product */
        $product = $productVariant->getProduct();
        Assert::notNull($product);
        Assert::isInstanceOf($product, PriceTiersAwareInterface::class);

        return $product->getPriceTiers()->toArray();
    }
}
