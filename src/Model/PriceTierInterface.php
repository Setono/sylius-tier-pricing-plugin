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
     * Passing null is a no-op (leaves the existing quantity untouched). This lets the type be used inside a `LiveCollectionType`, which re-binds the parent form with empty data on `addCollectionItem` before the user has typed anything.
     */
    public function setQuantity(?int $quantity): void;

    public function getDiscount(): string;

    /**
     * Passing null is a no-op (leaves the existing discount untouched). Same reason as `setQuantity()`.
     */
    public function setDiscount(float|string|null $discount): void;

    public function getProduct(): ?ProductInterface;

    public function setProduct(?ProductInterface $product): void;

    public function getProductVariant(): ?ProductVariantInterface;

    public function setProductVariant(?ProductVariantInterface $productVariant): void;
}
