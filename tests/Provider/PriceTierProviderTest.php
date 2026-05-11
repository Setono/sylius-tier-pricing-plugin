<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Provider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Setono\SyliusTierPricingPlugin\Provider\PriceTierProvider;
use Setono\SyliusTierPricingPlugin\Tests\Model\Fixture\ProductTraitFixture;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ProductVariant;

final class PriceTierProviderTest extends TestCase
{
    private const CHANNEL_CODE = 'WEB';

    private const OTHER_CHANNEL_CODE = 'WEB2';

    private const VARIANT_CODE = 'PRODUCT_VARIANT';

    private const OTHER_VARIANT_CODE = 'PRODUCT_VARIANT_2';

    private ProductTraitFixture $product;

    private ProductVariant $variant;

    private Channel $channel;

    protected function setUp(): void
    {
        $this->product = new ProductTraitFixture();
        $this->variant = $this->variant(self::VARIANT_CODE);
        $this->channel = $this->channel(self::CHANNEL_CODE);
    }

    // -----------------------------------------------------------------------
    // getPriceTiers()
    // -----------------------------------------------------------------------

    #[Test]
    public function it_returns_no_tiers_for_a_product_without_any(): void
    {
        $provider = $this->provider();

        self::assertSame([], $provider->getPriceTiers($this->variant, $this->channel));
    }

    #[Test]
    public function it_filters_out_tiers_belonging_to_a_different_channel(): void
    {
        $this->product->addPriceTier($this->priceTier(2, channelCode: self::OTHER_CHANNEL_CODE));
        $this->product->addPriceTier($matchingTier = $this->priceTier(5));

        $tiers = $this->provider()->getPriceTiers($this->variant, $this->channel);

        self::assertSame([$matchingTier], $tiers);
    }

    #[Test]
    public function it_filters_out_tiers_belonging_to_a_different_variant(): void
    {
        $this->product->addPriceTier($this->priceTier(2, productVariantCode: self::OTHER_VARIANT_CODE));
        $this->product->addPriceTier($matchingTier = $this->priceTier(5));

        $tiers = $this->provider()->getPriceTiers($this->variant, $this->channel);

        self::assertSame([$matchingTier], $tiers);
    }

    #[Test]
    public function it_returns_tiers_sorted_by_ascending_quantity_even_when_added_out_of_order(): void
    {
        $this->product->addPriceTier($tier10 = $this->priceTier(10));
        $this->product->addPriceTier($tier2 = $this->priceTier(2));
        $this->product->addPriceTier($tier5 = $this->priceTier(5));

        $tiers = $this->provider()->getPriceTiers($this->variant, $this->channel);

        self::assertSame([$tier2, $tier5, $tier10], $tiers);
    }

    #[Test]
    public function it_prefers_channel_and_variant_scoped_tier_over_less_specific_tiers_at_the_same_quantity(): void
    {
        $this->product->addPriceTier($this->priceTier(5));
        $this->product->addPriceTier($this->priceTier(5, channelCode: self::CHANNEL_CODE));
        $this->product->addPriceTier($this->priceTier(5, productVariantCode: self::VARIANT_CODE));
        $this->product->addPriceTier($mostSpecific = $this->priceTier(5, self::CHANNEL_CODE, self::VARIANT_CODE));

        $tiers = $this->provider()->getPriceTiers($this->variant, $this->channel);

        self::assertSame([$mostSpecific], $tiers);
    }

    #[Test]
    public function it_prefers_variant_scoped_tier_over_channel_only_or_generic_at_the_same_quantity(): void
    {
        $this->product->addPriceTier($this->priceTier(5));
        $this->product->addPriceTier($this->priceTier(5, channelCode: self::CHANNEL_CODE));
        $this->product->addPriceTier($variantScoped = $this->priceTier(5, productVariantCode: self::VARIANT_CODE));

        $tiers = $this->provider()->getPriceTiers($this->variant, $this->channel);

        self::assertSame([$variantScoped], $tiers);
    }

    #[Test]
    public function it_prefers_channel_scoped_tier_over_generic_at_the_same_quantity(): void
    {
        $this->product->addPriceTier($this->priceTier(5));
        $this->product->addPriceTier($channelScoped = $this->priceTier(5, channelCode: self::CHANNEL_CODE));

        $tiers = $this->provider()->getPriceTiers($this->variant, $this->channel);

        self::assertSame([$channelScoped], $tiers);
    }

    #[Test]
    public function it_falls_back_to_the_channel_context_when_no_channel_is_passed(): void
    {
        $contextChannel = $this->channel(self::CHANNEL_CODE);
        $this->product->addPriceTier($expected = $this->priceTier(5, channelCode: self::CHANNEL_CODE));
        $this->product->addPriceTier($this->priceTier(5, channelCode: self::OTHER_CHANNEL_CODE));

        $tiers = $this->provider($contextChannel)->getPriceTiers($this->variant);

        self::assertSame([$expected], $tiers);
    }

    // -----------------------------------------------------------------------
    // getPriceTier()
    // -----------------------------------------------------------------------

    #[Test]
    public function it_returns_null_when_no_tier_applies_to_the_quantity(): void
    {
        $this->product->addPriceTier($this->priceTier(5));

        self::assertNull($this->provider()->getPriceTier(4, $this->variant, $this->channel));
    }

    #[Test]
    public function it_returns_null_when_the_product_has_no_tiers(): void
    {
        self::assertNull($this->provider()->getPriceTier(100, $this->variant, $this->channel));
    }

    #[Test]
    public function it_returns_the_exact_match_when_quantity_equals_a_tier_boundary(): void
    {
        $this->product->addPriceTier($tier5 = $this->priceTier(5));
        $this->product->addPriceTier($tier10 = $this->priceTier(10));

        self::assertSame($tier5, $this->provider()->getPriceTier(5, $this->variant, $this->channel));
        self::assertSame($tier10, $this->provider()->getPriceTier(10, $this->variant, $this->channel));
    }

    #[Test]
    public function it_returns_the_highest_tier_the_quantity_meets(): void
    {
        $this->product->addPriceTier($this->priceTier(5));
        $this->product->addPriceTier($tier10 = $this->priceTier(10));
        $this->product->addPriceTier($tier20 = $this->priceTier(20));

        self::assertSame($tier10, $this->provider()->getPriceTier(11, $this->variant, $this->channel));
        self::assertSame($tier10, $this->provider()->getPriceTier(19, $this->variant, $this->channel));
        self::assertSame($tier20, $this->provider()->getPriceTier(20, $this->variant, $this->channel));
        self::assertSame($tier20, $this->provider()->getPriceTier(9999, $this->variant, $this->channel));
    }

    // -----------------------------------------------------------------------
    // Integration: precedence + multiple quantities resolution
    // -----------------------------------------------------------------------

    #[Test]
    public function it_resolves_one_tier_per_quantity_applying_full_precedence_rules(): void
    {
        // qty 2 — only generic matches; channel WEB2 / variant_2 filtered out.
        $this->product->addPriceTier($expected2 = $this->priceTier(2));
        $this->product->addPriceTier($this->priceTier(2, channelCode: self::OTHER_CHANNEL_CODE));
        $this->product->addPriceTier($this->priceTier(2, productVariantCode: self::OTHER_VARIANT_CODE));

        // qty 5 — channel+variant wins over variant-only / channel-only / generic.
        $this->product->addPriceTier($this->priceTier(5, productVariantCode: self::VARIANT_CODE));
        $this->product->addPriceTier($expected5 = $this->priceTier(5, self::CHANNEL_CODE, self::VARIANT_CODE));
        $this->product->addPriceTier($this->priceTier(5, channelCode: self::CHANNEL_CODE));
        $this->product->addPriceTier($this->priceTier(5));

        // qty 10 — variant-only wins over generic / channel-only (channel WEB2/variant filtered out).
        $this->product->addPriceTier($this->priceTier(10));
        $this->product->addPriceTier($this->priceTier(10, channelCode: self::CHANNEL_CODE));
        $this->product->addPriceTier($expected10 = $this->priceTier(10, productVariantCode: self::VARIANT_CODE));
        $this->product->addPriceTier($this->priceTier(10, self::OTHER_CHANNEL_CODE, self::VARIANT_CODE));

        // qty 15 — variant-only wins; competing tier for other variant filtered out.
        $this->product->addPriceTier($this->priceTier(15));
        $this->product->addPriceTier($this->priceTier(15, channelCode: self::CHANNEL_CODE));
        $this->product->addPriceTier($expected15 = $this->priceTier(15, productVariantCode: self::VARIANT_CODE));
        $this->product->addPriceTier($this->priceTier(15, self::CHANNEL_CODE, self::OTHER_VARIANT_CODE));

        // qty 20 — channel-only wins; competing variant_2 tiers filtered out.
        $this->product->addPriceTier($this->priceTier(20));
        $this->product->addPriceTier($expected20 = $this->priceTier(20, channelCode: self::CHANNEL_CODE));
        $this->product->addPriceTier($this->priceTier(20, productVariantCode: self::OTHER_VARIANT_CODE));
        $this->product->addPriceTier($this->priceTier(20, self::OTHER_CHANNEL_CODE, self::OTHER_VARIANT_CODE));

        $provider = $this->provider();

        self::assertSame(
            [$expected2, $expected5, $expected10, $expected15, $expected20],
            $provider->getPriceTiers($this->variant, $this->channel),
        );

        self::assertNull($provider->getPriceTier(1, $this->variant, $this->channel));
        self::assertSame($expected2, $provider->getPriceTier(2, $this->variant, $this->channel));
        self::assertSame($expected5, $provider->getPriceTier(5, $this->variant, $this->channel));
        self::assertSame($expected10, $provider->getPriceTier(11, $this->variant, $this->channel));
        self::assertSame($expected15, $provider->getPriceTier(15, $this->variant, $this->channel));
        self::assertSame($expected20, $provider->getPriceTier(20, $this->variant, $this->channel));
    }

    // -----------------------------------------------------------------------
    // helpers
    // -----------------------------------------------------------------------

    private function provider(?ChannelInterface $contextChannel = null): PriceTierProvider
    {
        $channelContext = $this->createMock(ChannelContextInterface::class);
        if (null !== $contextChannel) {
            $channelContext->method('getChannel')->willReturn($contextChannel);
        }

        return new PriceTierProvider($channelContext);
    }

    private function priceTier(int $quantity, ?string $channelCode = null, ?string $productVariantCode = null): PriceTier
    {
        $priceTier = new PriceTier();
        $priceTier->setQuantity($quantity);

        if (null !== $channelCode) {
            $priceTier->setChannel($this->channel($channelCode));
        }

        if (null !== $productVariantCode) {
            $priceTier->setProductVariant($this->variant($productVariantCode));
        }

        return $priceTier;
    }

    private function variant(string $code): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->setCode($code);
        $variant->setProduct($this->product);

        return $variant;
    }

    private function channel(string $code): Channel
    {
        $channel = new Channel();
        $channel->setCode($code);

        return $channel;
    }
}
