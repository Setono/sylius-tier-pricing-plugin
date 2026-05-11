<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Form\Type;

use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierCollectionType;
use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierType;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantChoiceType;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

final class PriceTierCollectionTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    private Channel $channel;

    protected function setUp(): void
    {
        $this->channel = $this->channel('WEB');

        parent::setUp();
    }

    // -----------------------------------------------------------------------
    // configureOptions() — option defaults
    // -----------------------------------------------------------------------

    #[Test]
    public function it_extends_live_collection_type_so_add_and_delete_fire_server_side_via_live_components(): void
    {
        self::assertSame(LiveCollectionType::class, (new PriceTierCollectionType())->getParent());
    }

    #[Test]
    public function it_uses_price_tier_type_as_the_entry_type_with_a_blank_per_row_label(): void
    {
        $options = $this->resolveOptions();

        self::assertSame(PriceTierType::class, $options['entry_type']);
        self::assertSame(['label' => false], $options['entry_options']);
    }

    #[Test]
    public function it_allows_adding_and_deleting_rows(): void
    {
        $options = $this->resolveOptions();

        self::assertTrue($options['allow_add']);
        self::assertTrue($options['allow_delete']);
    }

    #[Test]
    public function it_disables_by_reference_so_the_owning_side_of_the_relation_is_updated_on_save(): void
    {
        self::assertFalse($this->resolveOptions()['by_reference']);
    }

    #[Test]
    public function it_pins_block_name_to_entry_matching_the_sylius_image_collection_convention(): void
    {
        self::assertSame('entry', $this->resolveOptions()['block_name']);
    }

    #[Test]
    public function it_labels_the_add_button_with_the_plugin_translation_key(): void
    {
        self::assertSame(
            ['label' => 'setono_sylius_tier_pricing.ui.add_price_tier'],
            $this->resolveOptions()['button_add_options'],
        );
    }

    #[Test]
    public function it_exposes_a_predictable_block_prefix_for_template_overrides(): void
    {
        self::assertSame(
            'setono_sylius_tier_pricing_price_tier_collection',
            (new PriceTierCollectionType())->getBlockPrefix(),
        );
    }

    // -----------------------------------------------------------------------
    // Live decoration — the contract that makes Add/Delete fire over the wire.
    // Mirrors symfony/ux-live-component's own LiveCollectionTypeTest.
    // -----------------------------------------------------------------------

    #[Test]
    public function the_add_button_view_carries_the_live_collection_button_add_block_prefix(): void
    {
        $blockPrefixes = $this->buttonAddView()->vars['block_prefixes'];
        assert(is_array($blockPrefixes));

        self::assertContains('live_collection_button_add', $blockPrefixes);
    }

    #[Test]
    public function the_add_button_view_emits_the_live_action_attributes_for_addCollectionItem(): void
    {
        $attr = $this->buttonAddView()->vars['attr'];
        assert(is_array($attr));

        self::assertSame('live#action', $attr['data-action']);
        self::assertSame('addCollectionItem', $attr['data-live-action-param']);
        self::assertSame('price_tiers', $attr['data-live-name-param']);
    }

    #[Test]
    public function each_entry_view_carries_a_delete_button_with_the_live_collection_button_delete_block_prefix(): void
    {
        $view = $this->factory
            ->createNamed('price_tiers', PriceTierCollectionType::class, [new PriceTier()])
            ->createView();
        self::assertCount(1, $view);

        $buttonDelete = $view[0]->vars['button_delete'];
        assert($buttonDelete instanceof FormView);
        $blockPrefixes = $buttonDelete->vars['block_prefixes'];
        assert(is_array($blockPrefixes));

        self::assertContains('live_collection_button_delete', $blockPrefixes);
    }

    #[Test]
    public function the_add_button_label_resolves_to_the_plugin_translation_key(): void
    {
        self::assertSame(
            'setono_sylius_tier_pricing.ui.add_price_tier',
            $this->buttonAddView()->vars['label'],
        );
    }

    // -----------------------------------------------------------------------
    // Data binding — submit a list of entries, verify each becomes a PriceTier.
    // -----------------------------------------------------------------------

    #[Test]
    public function submitting_an_array_of_payloads_produces_one_price_tier_per_entry(): void
    {
        $form = $this->factory->createNamed('price_tiers', PriceTierCollectionType::class);
        $form->submit([
            ['quantity' => '5', 'discount' => '10', 'channel' => 'WEB'],
            ['quantity' => '10', 'discount' => '20', 'channel' => ''],
        ]);

        self::assertTrue($form->isSynchronized());

        /** @var list<PriceTier> $tiers */
        $tiers = $form->getData();
        self::assertCount(2, $tiers);
        self::assertContainsOnlyInstancesOf(PriceTier::class, $tiers);
        self::assertSame(5, $tiers[0]->getQuantity());
        self::assertSame($this->channel, $tiers[0]->getChannel());
        self::assertSame(10, $tiers[1]->getQuantity());
        self::assertNull($tiers[1]->getChannel());
    }

    protected function getTypes(): array
    {
        return [
            new PriceTierType(PriceTier::class, ['setono_sylius_tier_pricing']),
            new ChannelChoiceType($this->channelRepository()),
            new ProductVariantChoiceType(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveOptions(): array
    {
        $resolver = new OptionsResolver();
        (new PriceTierCollectionType())->configureOptions($resolver);

        /** @var array<string, mixed> $resolved */
        $resolved = $resolver->resolve();

        return $resolved;
    }

    private function buttonAddView(): FormView
    {
        $view = $this->factory
            ->createNamed('price_tiers', PriceTierCollectionType::class, [])
            ->createView();
        $buttonAdd = $view->vars['button_add'];
        assert($buttonAdd instanceof FormView);

        return $buttonAdd;
    }

    /** @return RepositoryInterface<ChannelInterface> */
    private function channelRepository(): RepositoryInterface
    {
        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findAll()->willReturn([$this->channel]);

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
