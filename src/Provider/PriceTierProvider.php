<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Provider;

use Setono\SyliusTierPricingPlugin\Model\PriceTierInterface;
use Setono\SyliusTierPricingPlugin\Model\ProductInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class PriceTierProvider implements PriceTierProviderInterface
{
    public function __construct(private readonly ChannelContextInterface $channelContext)
    {
    }

    public function getPriceTier(
        int $quantity,
        ProductVariantInterface $productVariant,
        ChannelInterface $channel = null,
    ): ?PriceTierInterface {
        $resolvedPriceTier = null;

        foreach ($this->getPriceTiers($productVariant, $channel) as $priceTier) {
            if ($quantity < $priceTier->getQuantity()) {
                continue;
            }

            if (null === $resolvedPriceTier) {
                $resolvedPriceTier = $priceTier;
            }

            if ($priceTier->getQuantity() > $resolvedPriceTier->getQuantity()) {
                $resolvedPriceTier = $priceTier;
            }
        }

        return $resolvedPriceTier;
    }

    public function getPriceTiers(ProductVariantInterface $productVariant, ChannelInterface $channel = null): array
    {
        $channel = $channel ?? $this->channelContext->getChannel();

        /** @var ProductInterface|null $product */
        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);

        /** @var array<int, list<PriceTierInterface>> $quantities */
        $quantities = [];

        foreach ($product->getPriceTiers() as $priceTier) {
            if ($priceTier->getChannel() !== null && $priceTier->getChannel()?->getCode() !== $channel->getCode()) {
                continue;
            }

            if ($priceTier->getProductVariant() !== null && $priceTier->getProductVariant()?->getCode() !== $productVariant->getCode()) {
                continue;
            }

            $quantities[$priceTier->getQuantity()][] = $priceTier;
        }

        $resolvedPriceTiers = [];

        foreach ($quantities as $priceTiers) {
            if ([] === $priceTiers) {
                continue;
            }

            usort($priceTiers, static function (PriceTierInterface $priceTier1, PriceTierInterface $priceTier2) {
                if ($priceTier1->getChannel() !== null && $priceTier1->getProductVariant() !== null) {
                    return -1;
                }

                if ($priceTier2->getChannel() !== null && $priceTier2->getProductVariant() !== null) {
                    return 1;
                }

                if ($priceTier1->getProductVariant() !== null) {
                    return -1;
                }

                if ($priceTier2->getProductVariant() !== null) {
                    return 1;
                }

                if ($priceTier1->getChannel() !== null) {
                    return -1;
                }

                if ($priceTier2->getChannel() !== null) {
                    return 1;
                }

                return 0;
            });

            $resolvedPriceTiers[] = $priceTiers[0];
        }

        return $resolvedPriceTiers;
    }
}
