<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface PriceTierInterface extends ResourceInterface
{
    public function getId(): ?int;

    /**
     * The quantity that triggers this price tier
     */
    public function getQuantity(): int;

    public function setQuantity(int $quantity): void;

    public function getDiscount(): float;

    public function setDiscount(float $discount): void;
}
