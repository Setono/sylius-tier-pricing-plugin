<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

class PriceTier implements PriceTierInterface
{
    protected ?int $id = null;

    protected int $quantity = 1;

    protected float $discount = 0.0;

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
}
