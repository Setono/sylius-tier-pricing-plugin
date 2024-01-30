<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\OrderProcessor;

use Setono\SyliusTierPricingPlugin\Provider\PriceTierProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;

final class PriceTiersOrderProcessor implements OrderProcessorInterface
{
    final public const PRICE_TIER_ADJUSTMENT = 'price_tier';

    public function __construct(
        private readonly PriceTierProviderInterface $priceTierProvider,
        private readonly AdjustmentFactoryInterface $adjustmentFactory,
    ) {
    }

    public function process(BaseOrderInterface $order): void
    {
        if (!$order instanceof OrderInterface) {
            return;
        }

        foreach ($order->getItems() as $item) {
            $item->removeAdjustments(self::PRICE_TIER_ADJUSTMENT);

            $variant = $item->getVariant();

            if (null === $variant) {
                continue;
            }

            $priceTier = $this->priceTierProvider->getPriceTier($item->getQuantity(), $variant, $order->getChannel());
            if (null === $priceTier) {
                continue;
            }

            $discount = (int) ceil($item->getFullDiscountedUnitPrice() / 100 * $priceTier->getDiscount());

            $adjustment = $this->adjustmentFactory->createWithData(
                self::PRICE_TIER_ADJUSTMENT,
                'setono_sylius_tier_pricing.ui.adjustment_label',
                -1 * $discount,
                false,
                [
                    'priceTierQuantity' => $priceTier->getQuantity(),
                    'priceTierDiscount' => $priceTier->getDiscount(),
                ],
            );

            $item->addAdjustment($adjustment);
        }
    }
}
