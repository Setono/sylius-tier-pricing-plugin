<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Form\Type;

use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierType;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Setono\SyliusTierPricingPlugin\Tests\Model\Fixture\ProductTraitFixture;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantChoiceType;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Form\Test\TypeTestCase;

final class PriceTierTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    private Channel $channelOne;

    private Channel $channelTwo;

    protected function setUp(): void
    {
        $this->channelOne = $this->channel('CH1');
        $this->channelTwo = $this->channel('CH2');

        parent::setUp();
    }

    #[Test]
    public function it_binds_quantity_discount_and_channel_into_a_price_tier(): void
    {
        $form = $this->factory->create(PriceTierType::class);

        $form->submit([
            'quantity' => '5',
            'discount' => '10.0',
            'channel' => 'CH1',
        ]);

        self::assertTrue($form->isSynchronized());

        /** @var PriceTier $priceTier */
        $priceTier = $form->getData();
        self::assertInstanceOf(PriceTier::class, $priceTier);
        self::assertSame(5, $priceTier->getQuantity());
        // NumberType with scale=7 reverse-transforms '10.0' through the locale formatter,
        // which round-trips to the canonical 7-decimal form when written back to the model.
        self::assertSame('10.0000000', $priceTier->getDiscount());
        self::assertSame($this->channelOne, $priceTier->getChannel());
    }

    #[Test]
    public function empty_quantity_and_discount_are_no_ops_so_live_collection_re_bind_does_not_throw(): void
    {
        $form = $this->factory->create(PriceTierType::class);

        $form->submit([
            'quantity' => '',
            'discount' => '',
            'channel' => '',
        ]);

        self::assertTrue($form->isSynchronized());

        /** @var PriceTier $priceTier */
        $priceTier = $form->getData();
        self::assertSame(1, $priceTier->getQuantity());
        self::assertSame('0.0', $priceTier->getDiscount());
        self::assertNull($priceTier->getChannel());
    }

    #[Test]
    public function it_adds_the_product_variant_field_when_the_price_tier_has_a_product(): void
    {
        // A product with no variants keeps the test independent of Sylius's translatable variant labelling
        // (ProductVariantChoiceType labels by name, which requires a current locale on the variant).
        $product = new ProductTraitFixture();
        $priceTier = new PriceTier();
        $priceTier->setProduct($product);

        $form = $this->factory->create(PriceTierType::class, $priceTier);

        self::assertTrue($form->has('productVariant'));
    }

    #[Test]
    public function it_does_not_add_the_product_variant_field_when_no_initial_data_is_given(): void
    {
        $form = $this->factory->create(PriceTierType::class);

        self::assertFalse($form->has('productVariant'));
    }

    #[Test]
    public function it_does_not_add_the_product_variant_field_when_the_price_tier_has_no_product(): void
    {
        $priceTier = new PriceTier();

        $form = $this->factory->create(PriceTierType::class, $priceTier);

        self::assertFalse($form->has('productVariant'));
    }

    #[Test]
    public function it_uses_the_price_tier_class_as_the_data_class(): void
    {
        $form = $this->factory->create(PriceTierType::class);

        self::assertSame(PriceTier::class, $form->getConfig()->getDataClass());
    }

    #[Test]
    public function it_propagates_validation_groups_from_the_resource_type_to_the_form(): void
    {
        $form = $this->factory->create(PriceTierType::class);

        self::assertSame(['setono_sylius_tier_pricing'], $form->getConfig()->getOption('validation_groups'));
    }

    #[Test]
    public function it_exposes_a_predictable_block_prefix_for_template_overrides(): void
    {
        self::assertSame(
            'setono_sylius_tier_pricing_price_tier',
            (new PriceTierType(PriceTier::class))->getBlockPrefix(),
        );
    }

    protected function getTypes(): array
    {
        return [
            new PriceTierType(PriceTier::class, ['setono_sylius_tier_pricing']),
            new ChannelChoiceType($this->channelRepository()),
            new ProductVariantChoiceType(),
        ];
    }

    /** @return RepositoryInterface<ChannelInterface> */
    private function channelRepository(): RepositoryInterface
    {
        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findAll()->willReturn([$this->channelOne, $this->channelTwo]);

        return $repository->reveal();
    }

    private function channel(string $code): Channel
    {
        $channel = new Channel();
        $channel->setCode($code);
        $channel->setName($code);

        return $channel;
    }
}
