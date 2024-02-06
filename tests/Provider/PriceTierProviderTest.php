<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Setono\SyliusTierPricingPlugin\Model\ProductInterface;
use Setono\SyliusTierPricingPlugin\Model\ProductTrait;
use Setono\SyliusTierPricingPlugin\Provider\PriceTierProvider;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;

final class PriceTierProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_works(): void
    {
        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $priceTierProvider = new PriceTierProvider($channelContext->reveal());

        $channel = new Channel();
        $channel->setCode('WEB');

        $product = new ProductWithPriceTiers();

        // price tiers with qty equals 2
        $product->addPriceTier($expected2 = self::getPriceTier(2));
        $product->addPriceTier(self::getPriceTier(2, 'WEB2'));
        $product->addPriceTier(self::getPriceTier(2, productVariantCode: 'PRODUCT_VARIANT_2'));

        // price tiers with qty equals 5
        $product->addPriceTier(self::getPriceTier(5, productVariantCode: 'PRODUCT_VARIANT'));
        $product->addPriceTier($expected5 = self::getPriceTier(5, 'WEB', 'PRODUCT_VARIANT'));
        $product->addPriceTier(self::getPriceTier(5, 'WEB'));
        $product->addPriceTier(self::getPriceTier(5));

        // price tiers with qty equals 10
        $product->addPriceTier(self::getPriceTier(10));
        $product->addPriceTier(self::getPriceTier(10, 'WEB'));
        $product->addPriceTier($expected10 = self::getPriceTier(10, productVariantCode: 'PRODUCT_VARIANT'));
        $product->addPriceTier(self::getPriceTier(10, 'WEB2', 'PRODUCT_VARIANT'));

        // price tiers with qty equals 15
        $product->addPriceTier(self::getPriceTier(15));
        $product->addPriceTier(self::getPriceTier(15, 'WEB'));
        $product->addPriceTier($expected15 = self::getPriceTier(15, productVariantCode: 'PRODUCT_VARIANT'));
        $product->addPriceTier(self::getPriceTier(15, 'WEB', 'PRODUCT_VARIANT_2'));

        // price tiers with qty equals 20
        $product->addPriceTier(self::getPriceTier(20));
        $product->addPriceTier($expected20 = self::getPriceTier(20, 'WEB'));
        $product->addPriceTier(self::getPriceTier(20, productVariantCode: 'PRODUCT_VARIANT_2'));
        $product->addPriceTier(self::getPriceTier(20, 'WEB2', 'PRODUCT_VARIANT_2'));

        $productVariant = new ProductVariant();
        $productVariant->setProduct($product);
        $productVariant->setCode('PRODUCT_VARIANT');

        $priceTiers = $priceTierProvider->getPriceTiers($productVariant, $channel);

        self::assertCount(5, $priceTiers);
        self::assertSame($expected2, $priceTiers[0]);
        self::assertSame($expected5, $priceTiers[1]);
        self::assertSame($expected10, $priceTiers[2]);
        self::assertSame($expected15, $priceTiers[3]);
        self::assertSame($expected20, $priceTiers[4]);

        $priceTier = $priceTierProvider->getPriceTier(1, $productVariant, $channel);
        self::assertNull($priceTier);

        $priceTier = $priceTierProvider->getPriceTier(2, $productVariant, $channel);
        self::assertSame($expected2, $priceTier);

        $priceTier = $priceTierProvider->getPriceTier(5, $productVariant, $channel);
        self::assertSame($expected5, $priceTier);

        $priceTier = $priceTierProvider->getPriceTier(11, $productVariant, $channel);
        self::assertSame($expected10, $priceTier);

        $priceTier = $priceTierProvider->getPriceTier(15, $productVariant, $channel);
        self::assertSame($expected15, $priceTier);

        $priceTier = $priceTierProvider->getPriceTier(20, $productVariant, $channel);
        self::assertSame($expected20, $priceTier);
    }

    private static function getPriceTier(int $quantity, string $channelCode = null, string $productVariantCode = null): PriceTier
    {
        $priceTier = new PriceTier();
        $priceTier->setQuantity($quantity);

        if (null !== $channelCode) {
            $channel = new Channel();
            $channel->setCode($channelCode);
            $priceTier->setChannel($channel);
        }

        if (null !== $productVariantCode) {
            $productVariant = new ProductVariant();
            $productVariant->setCode($productVariantCode);
            $priceTier->setProductVariant($productVariant);
        }

        return $priceTier;
    }
}

final class ProductWithPriceTiers extends Product implements ProductInterface
{
    use ProductTrait {
        __construct as private initializePriceTiersCollection;
    }

    public function __construct()
    {
        parent::__construct();

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->code = null;

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->mainTaxon = null;

        $this->initializePriceTiersCollection();
    }
}
