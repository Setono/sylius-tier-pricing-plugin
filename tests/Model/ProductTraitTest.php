<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Setono\SyliusTierPricingPlugin\Tests\Model\Fixture\ProductTraitFixture;

final class ProductTraitTest extends TestCase
{
    #[Test]
    public function it_starts_with_an_empty_collection(): void
    {
        $product = new ProductTraitFixture();

        self::assertTrue($product->getPriceTiers()->isEmpty());
    }

    #[Test]
    public function it_adds_a_price_tier_and_assigns_the_product_back(): void
    {
        $product = new ProductTraitFixture();
        $priceTier = new PriceTier();

        $product->addPriceTier($priceTier);

        self::assertCount(1, $product->getPriceTiers());
        self::assertTrue($product->getPriceTiers()->contains($priceTier));
        self::assertSame($product, $priceTier->getProduct());
    }

    #[Test]
    public function it_does_not_add_the_same_price_tier_twice(): void
    {
        $product = new ProductTraitFixture();
        $priceTier = new PriceTier();

        $product->addPriceTier($priceTier);
        $product->addPriceTier($priceTier);

        self::assertCount(1, $product->getPriceTiers());
    }

    #[Test]
    public function it_removes_a_price_tier_and_clears_its_product(): void
    {
        $product = new ProductTraitFixture();
        $priceTier = new PriceTier();

        $product->addPriceTier($priceTier);
        $product->removePriceTier($priceTier);

        self::assertCount(0, $product->getPriceTiers());
        self::assertNull($priceTier->getProduct());
    }

    #[Test]
    public function removing_a_price_tier_that_is_not_in_the_collection_is_a_no_op(): void
    {
        $product = new ProductTraitFixture();
        $priceTier = new PriceTier();
        // attach the tier to a different product, then call remove on `product` — the
        // foreign product's link must not be cleared, and the collection on `product`
        // must remain empty.
        $other = new ProductTraitFixture();
        $other->addPriceTier($priceTier);

        $product->removePriceTier($priceTier);

        self::assertCount(0, $product->getPriceTiers());
        self::assertCount(1, $other->getPriceTiers());
        self::assertSame($other, $priceTier->getProduct());
    }
}
