<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface PriceTierInterface extends ResourceInterface, ChannelAwareInterface
{
    public function getId(): ?int;

    /**
     * The quantity that triggers this price tier
     */
    public function getQuantity(): int;

    public function setQuantity(int $quantity): void;

    public function getDiscount(): float;

    public function setDiscount(float $discount): void;

    public function getProduct(): ?ProductInterface;

    public function setProduct(?ProductInterface $product): void;

    public function getProductVariant(): ?ProductVariantInterface;

    public function setProductVariant(?ProductVariantInterface $productVariant): void;
}
