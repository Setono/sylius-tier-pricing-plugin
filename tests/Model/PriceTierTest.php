<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;

final class PriceTierTest extends TestCase
{
    #[Test]
    public function it_has_no_id_until_persisted(): void
    {
        $priceTier = new PriceTier();

        self::assertNull($priceTier->getId());
    }

    #[Test]
    public function it_defaults_quantity_to_1(): void
    {
        $priceTier = new PriceTier();

        self::assertSame(1, $priceTier->getQuantity());
    }

    #[Test]
    public function it_defaults_discount_to_zero(): void
    {
        $priceTier = new PriceTier();

        self::assertSame('0.0', $priceTier->getDiscount());
    }

    #[Test]
    public function it_has_no_product_product_variant_or_channel_by_default(): void
    {
        $priceTier = new PriceTier();

        self::assertNull($priceTier->getProduct());
        self::assertNull($priceTier->getProductVariant());
        self::assertNull($priceTier->getChannel());
    }

    #[Test]
    public function it_stores_quantity(): void
    {
        $priceTier = new PriceTier();
        $priceTier->setQuantity(5);

        self::assertSame(5, $priceTier->getQuantity());
    }

    #[Test]
    public function it_stores_discount_from_a_string(): void
    {
        $priceTier = new PriceTier();
        $priceTier->setDiscount('12.5');

        self::assertSame('12.5', $priceTier->getDiscount());
    }

    #[Test]
    public function it_stores_discount_from_a_float_by_casting_to_string(): void
    {
        $priceTier = new PriceTier();
        $priceTier->setDiscount(12.5);

        self::assertSame('12.5', $priceTier->getDiscount());
    }

    #[Test]
    public function setting_quantity_to_null_resets_it_to_the_default(): void
    {
        $priceTier = new PriceTier();
        $priceTier->setQuantity(5);
        $priceTier->setQuantity(null);

        self::assertSame(PriceTier::DEFAULT_QUANTITY, $priceTier->getQuantity());
    }

    #[Test]
    public function setting_discount_to_null_resets_it_to_the_default(): void
    {
        $priceTier = new PriceTier();
        $priceTier->setDiscount('12.5');
        $priceTier->setDiscount(null);

        self::assertSame(PriceTier::DEFAULT_DISCOUNT, $priceTier->getDiscount());
    }

    #[Test]
    public function it_stores_channel(): void
    {
        $priceTier = new PriceTier();
        $channel = new Channel();
        $priceTier->setChannel($channel);

        self::assertSame($channel, $priceTier->getChannel());

        $priceTier->setChannel(null);
        self::assertNull($priceTier->getChannel());
    }

    #[Test]
    public function it_syncs_product_when_a_variant_is_set(): void
    {
        $product = $this->productWithId(1);
        $variant = $this->variantOnProduct($product);

        $priceTier = new PriceTier();
        $priceTier->setProductVariant($variant);

        self::assertSame($variant, $priceTier->getProductVariant());
        self::assertSame($product, $priceTier->getProduct());
    }

    #[Test]
    public function it_clears_the_product_when_the_variant_is_unset(): void
    {
        $product = $this->productWithId(1);
        $variant = $this->variantOnProduct($product);

        $priceTier = new PriceTier();
        $priceTier->setProductVariant($variant);
        $priceTier->setProductVariant(null);

        self::assertNull($priceTier->getProductVariant());
        self::assertNull($priceTier->getProduct());
    }

    #[Test]
    public function it_keeps_the_variant_when_setting_the_same_product(): void
    {
        $product = $this->productWithId(1);
        $variant = $this->variantOnProduct($product);

        $priceTier = new PriceTier();
        $priceTier->setProductVariant($variant);
        $priceTier->setProduct($product);

        self::assertSame($variant, $priceTier->getProductVariant());
        self::assertSame($product, $priceTier->getProduct());
    }

    #[Test]
    public function it_clears_the_variant_when_setting_a_different_product(): void
    {
        $product = $this->productWithId(1);
        $variant = $this->variantOnProduct($product);

        $priceTier = new PriceTier();
        $priceTier->setProductVariant($variant);

        $otherProduct = $this->productWithId(2);
        $priceTier->setProduct($otherProduct);

        self::assertNull($priceTier->getProductVariant());
        self::assertSame($otherProduct, $priceTier->getProduct());
    }

    #[Test]
    public function it_keeps_the_variant_when_setting_product_to_null(): void
    {
        $product = $this->productWithId(1);
        $variant = $this->variantOnProduct($product);

        $priceTier = new PriceTier();
        $priceTier->setProductVariant($variant);
        $priceTier->setProduct(null);

        self::assertSame($variant, $priceTier->getProductVariant());
        self::assertNull($priceTier->getProduct());
    }

    #[Test]
    public function it_keeps_the_variant_when_setting_a_product_for_the_first_time(): void
    {
        $product = $this->productWithId(1);
        $variant = $this->variantOnProduct($product);

        $priceTier = new PriceTier();
        $priceTier->setProductVariant($variant);

        // First the syncing assigned the product; calling setProduct() with the same product again is a no-op.
        $priceTier->setProduct($product);

        self::assertSame($variant, $priceTier->getProductVariant());
        self::assertSame($product, $priceTier->getProduct());
    }

    private function productWithId(int $id): Product
    {
        $product = new Product();
        // Sylius's Product has its id as a protected property; use reflection rather than persisting through Doctrine.
        $reflection = new \ReflectionClass($product);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($product, $id);

        return $product;
    }

    private function variantOnProduct(Product $product): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->setProduct($product);

        return $variant;
    }
}
