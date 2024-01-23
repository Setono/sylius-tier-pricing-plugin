<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;

class PriceTier implements PriceTierInterface
{
    protected ?int $id = null;

    protected int $quantity = 1;

    protected float $discount = 0.0;

    protected ?ProductInterface $product = null;

    protected ?ProductVariantInterface $productVariant = null;

    protected ?ChannelInterface $channel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): void
    {
        $this->discount = $discount;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    public function setProduct(?ProductInterface $product): void
    {
        // this is a safety mechanism to ensure that a price tier cannot exist with a product variant that does not belong to the product
        if (null !== $product && null !== $this->product && $product->getId() !== $this->product->getId()) {
            $this->productVariant = null;
        }

        $this->product = $product;
    }

    public function getProductVariant(): ?ProductVariantInterface
    {
        return $this->productVariant;
    }

    public function setProductVariant(?ProductVariantInterface $productVariant): void
    {
        $this->productVariant = $productVariant;
        $this->product = $productVariant?->getProduct();
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }
}
