<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Form\Type;

use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierType;
use Setono\SyliusTierPricingPlugin\Form\Type\ProductVariantAutocompleteType;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Setono\SyliusTierPricingPlugin\Tests\Model\Fixture\ProductTraitFixture;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Product\Model\Product as BaseProduct;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Form\FormBuilderInterface;
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
    public function empty_quantity_and_discount_reset_to_defaults_so_live_collection_re_bind_does_not_throw(): void
    {
        // LiveCollectionType re-submits the parent form with empty entry data on `addCollectionItem`.
        // The setters absorb the resulting null by resetting to the model defaults — no type error, and
        // the entity ends up in a deterministic state regardless of any prior value.
        $form = $this->factory->create(PriceTierType::class);

        $form->submit([
            'quantity' => '',
            'discount' => '',
            'channel' => '',
        ]);

        self::assertTrue($form->isSynchronized());

        /** @var PriceTier $priceTier */
        $priceTier = $form->getData();
        self::assertSame(PriceTier::DEFAULT_QUANTITY, $priceTier->getQuantity());
        self::assertSame(PriceTier::DEFAULT_DISCOUNT, $priceTier->getDiscount());
        self::assertNull($priceTier->getChannel());
    }

    #[Test]
    public function it_adds_the_product_variant_autocomplete_when_product_has_id(): void
    {
        $product = $this->productWithId(42);

        // Drive buildForm() directly with a prophesized builder so we don't have to instantiate the full
        // autocomplete type chain (TranslatableAutocompleteType → BaseEntityAutocompleteType → EntityType)
        // — which would pull in LocaleContext, the URL generator, and ManagerRegistry just to test wiring.
        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder->add(Argument::any(), Argument::any(), Argument::any())->willReturn($builder);
        $builder
            ->add('productVariant', ProductVariantAutocompleteType::class, Argument::that(
                static function (array $options): bool {
                    $extraOptions = $options['extra_options'] ?? [];
                    self::assertIsArray($extraOptions);

                    return 42 === ($extraOptions['product_id'] ?? null) &&
                        false === $options['required'] &&
                        'sylius.ui.variant' === $options['label'];
                },
            ))
            ->shouldBeCalled()
            ->willReturn($builder)
        ;

        $type = new PriceTierType(PriceTier::class, ['setono_sylius_tier_pricing']);
        $type->buildForm($builder->reveal(), ['product' => $product]);
    }

    #[Test]
    public function it_omits_the_product_variant_field_when_product_has_no_id(): void
    {
        // A brand-new product (admin create flow) has no variants yet, so scoping the autocomplete to
        // its id is meaningless — skip the field entirely rather than fall back to an unscoped lookup.
        $product = new ProductTraitFixture();
        self::assertNull($product->getId());

        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder->add(Argument::any(), Argument::any(), Argument::any())->willReturn($builder);
        $builder->add('productVariant', Argument::cetera())->shouldNotBeCalled();

        $type = new PriceTierType(PriceTier::class, ['setono_sylius_tier_pricing']);
        $type->buildForm($builder->reveal(), ['product' => $product]);
    }

    #[Test]
    public function it_omits_the_product_variant_field_when_product_option_is_null(): void
    {
        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder->add(Argument::any(), Argument::any(), Argument::any())->willReturn($builder);
        $builder->add('productVariant', Argument::cetera())->shouldNotBeCalled();

        $type = new PriceTierType(PriceTier::class, ['setono_sylius_tier_pricing']);
        $type->buildForm($builder->reveal(), ['product' => null]);
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

    private function productWithId(int $id): ProductTraitFixture
    {
        $product = new ProductTraitFixture();
        (new \ReflectionProperty(BaseProduct::class, 'id'))->setValue($product, $id);

        return $product;
    }
}
