<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\OrderProcessor;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Setono\SyliusTierPricingPlugin\Provider\PriceTierProviderInterface;
use Sylius\Component\Core\Distributor\IntegerDistributorInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PriceTiersOrderProcessor implements OrderProcessorInterface
{
    public function __construct(
        private readonly PriceTierProviderInterface $priceTierProvider,
        private readonly AdjustmentFactoryInterface $adjustmentFactory,
        private readonly IntegerDistributorInterface $distributor,
        private readonly TranslatorInterface $translator,
        private readonly ?string $adjustment_origin_code,
    ) {
    }

    public function process(BaseOrderInterface $order): void
    {
        if (!$order instanceof OrderInterface) {
            return;
        }

        // Notice that we don't remove any adjustments here. This is because we use Sylius' internal AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT

        foreach ($order->getItems() as $item) {
            $variant = $item->getVariant();

            if (null === $variant) {
                continue;
            }

            $priceTier = $this->priceTierProvider->getPriceTier($item->getQuantity(), $variant, $order->getChannel());
            if (null === $priceTier) {
                continue;
            }

            // todo test this with many decimals on the discount
            $discount = BigDecimal::of($item->getTotal())
                ->multipliedBy(BigDecimal::of($priceTier->getDiscount()))
                ->dividedBy(100, 0, RoundingMode::CEILING)
                ->toInt()
            ;

            /** @var list<int> $split */
            $split = $this->distributor->distribute($discount, $item->getQuantity());

            $i = 0;
            foreach ($item->getUnits() as $unit) {
                $unitDiscount = $split[$i] ?? 0;
                if (0 === $unitDiscount) {
                    // todo log this, as this is unexpected
                    break;
                }

                $adjustment = $this->adjustmentFactory->createWithData(
                    AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT,
                    $this->translator->trans('setono_sylius_tier_pricing.ui.price_tier_adjustment_label', ['%quantity%' => $priceTier->getQuantity()]),
                    -1 * $unitDiscount,
                    false,
                    [
                        'priceTierQuantity' => $priceTier->getQuantity(),
                        'priceTierDiscount' => $priceTier->getDiscount(),
                    ],
                );

                if ($this->adjustment_origin_code !== null) {
                    $adjustment->setOriginCode($this->adjustment_origin_code);
                }

                $unit->addAdjustment($adjustment);

                ++$i;
            }
        }
    }
}
