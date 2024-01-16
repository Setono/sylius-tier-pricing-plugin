<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Provider;

use Setono\SyliusTierPricingPlugin\Model\PriceTiersAwareInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class ChannelPricingBasedPriceTierProvider extends AbstractPriceTierProvider
{
    public function __construct(private readonly ChannelContextInterface $channelContext)
    {
    }

    public function getPriceTiers(ProductVariantInterface $productVariant, ChannelInterface $channel = null): iterable
    {
        $channel = $channel ?? $this->channelContext->getChannel();
        Assert::isInstanceOf($channel, ChannelInterface::class);

        /** @var ChannelPricingInterface|PriceTiersAwareInterface|null $channelPricing */
        $channelPricing = $productVariant->getChannelPricingForChannel($channel);
        if (null === $channelPricing) {
            return [];
        }

        Assert::isInstanceOf($channelPricing, PriceTiersAwareInterface::class);

        return $channelPricing->getPriceTiers();
    }
}
