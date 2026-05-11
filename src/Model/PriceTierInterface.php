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

    /**
     * Passing null resets the quantity to {@see PriceTier::DEFAULT_QUANTITY}. This lets the type be used inside a `LiveCollectionType`, which re-binds the parent form with empty data on `addCollectionItem` before the user has typed anything — the reset is preferable to a no-op because it gives the model deterministic state regardless of what the previous value was.
     */
    public function setQuantity(?int $quantity): void;

    public function getDiscount(): string;

    /**
     * Passing null resets the discount to {@see PriceTier::DEFAULT_DISCOUNT}. Same reason as `setQuantity()`.
     */
    public function setDiscount(float|string|null $discount): void;

    public function getProduct(): ?ProductInterface;

    public function setProduct(?ProductInterface $product): void;

    public function getProductVariant(): ?ProductVariantInterface;

    public function setProductVariant(?ProductVariantInterface $productVariant): void;
}
