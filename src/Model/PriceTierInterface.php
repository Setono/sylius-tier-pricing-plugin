<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface PriceTierInterface extends ResourceInterface
{
    public function getId(): ?int;
}
