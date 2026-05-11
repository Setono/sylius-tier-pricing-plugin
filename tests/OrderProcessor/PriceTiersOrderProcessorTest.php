<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\OrderProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Setono\SyliusTierPricingPlugin\OrderProcessor\PriceTiersOrderProcessor;
use Setono\SyliusTierPricingPlugin\Provider\PriceTierProviderInterface;
use Sylius\Component\Core\Distributor\IntegerDistributorInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\OrderItemUnitInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\AdjustmentInterface as OrderAdjustmentInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PriceTiersOrderProcessorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<PriceTierProviderInterface> */
    private ObjectProphecy $priceTierProvider;

    /** @var ObjectProphecy<AdjustmentFactoryInterface<OrderAdjustmentInterface>> */
    private ObjectProphecy $adjustmentFactory;

    /** @var ObjectProphecy<IntegerDistributorInterface> */
    private ObjectProphecy $distributor;

    /** @var ObjectProphecy<TranslatorInterface> */
    private ObjectProphecy $translator;

    protected function setUp(): void
    {
        $this->priceTierProvider = $this->prophesize(PriceTierProviderInterface::class);
        // jangregor/phpstan-prophecy types $this->prophesize(X::class) as ObjectProphecy<X>, which loses
        // X's own generic parameter — re-annotate so the property's declared generic round-trips through.
        /** @var ObjectProphecy<AdjustmentFactoryInterface<OrderAdjustmentInterface>> $adjustmentFactory */
        $adjustmentFactory = $this->prophesize(AdjustmentFactoryInterface::class);
        $this->adjustmentFactory = $adjustmentFactory;
        $this->distributor = $this->prophesize(IntegerDistributorInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    #[Test]
    public function it_ignores_orders_that_are_not_core_orders(): void
    {
        // The provider is the first dependency the processor touches once it accepts an order — if it
        // gets called for a non-core order, the early-return guard regressed.
        $this->priceTierProvider->getPriceTier(Argument::cetera())->shouldNotBeCalled();

        $this->processor()->process($this->prophesize(BaseOrderInterface::class)->reveal());
    }

    #[Test]
    public function it_skips_items_without_a_variant(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($this->prophesize(ChannelInterface::class)->reveal());

        $item = $this->prophesize(OrderItemInterface::class);
        $item->getVariant()->willReturn(null);

        $order->getItems()->willReturn(new ArrayCollection([$item->reveal()]));

        $this->priceTierProvider->getPriceTier(Argument::cetera())->shouldNotBeCalled();

        $this->processor()->process($order->reveal());
    }

    #[Test]
    public function it_skips_items_when_the_provider_returns_no_tier(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel);

        $item = $this->prophesize(OrderItemInterface::class);
        $item->getVariant()->willReturn($variant);
        $item->getQuantity()->willReturn(3);

        $order->getItems()->willReturn(new ArrayCollection([$item->reveal()]));

        $this->priceTierProvider->getPriceTier(3, $variant, $channel)->willReturn(null);

        $this->adjustmentFactory->createWithData(Argument::cetera())->shouldNotBeCalled();
        $this->distributor->distribute(Argument::cetera())->shouldNotBeCalled();

        $this->processor()->process($order->reveal());
    }

    #[Test]
    public function it_attaches_one_tier_pricing_adjustment_per_unit_with_the_distributed_split(): void
    {
        // Total 1000 cents × 10% discount, rounded up, equals 100 cents; the distributor splits that
        // across 5 units; we expect 5 adjustments, each carrying the translated label, the negative
        // per-unit cents, the metadata triple, and the 'tier_pricing' origin code.
        $variant = $this->prophesize(ProductVariantInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $priceTier = new PriceTier();
        $priceTier->setQuantity(3);
        $priceTier->setDiscount('10');
        $this->setPriceTierId($priceTier, 99);

        $units = $this->units(5);

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel);

        $item = $this->prophesize(OrderItemInterface::class);
        $item->getVariant()->willReturn($variant);
        $item->getQuantity()->willReturn(5);
        $item->getTotal()->willReturn(1000);
        $item->getUnits()->willReturn(new ArrayCollection(array_map(static fn (ObjectProphecy $u) => $u->reveal(), $units)));

        $order->getItems()->willReturn(new ArrayCollection([$item->reveal()]));

        $this->priceTierProvider->getPriceTier(5, $variant, $channel)->willReturn($priceTier);
        $this->distributor->distribute(100, 5)->willReturn([20, 20, 20, 20, 20]);

        $this->translator
            ->trans('setono_sylius_tier_pricing.ui.price_tier_adjustment_label', ['%quantity%' => 3])
            ->willReturn('Discount when buying 3 pieces or more')
        ;

        // Each call returns a fresh adjustment prophecy so we can assert setOriginCode was invoked on it.
        $adjustments = [];
        for ($i = 0; $i < 5; ++$i) {
            $adjustment = $this->prophesize(AdjustmentInterface::class);
            $adjustment->setOriginCode('tier_pricing')->shouldBeCalled();
            $adjustments[] = $adjustment;
        }

        $callIndex = 0;
        $this->adjustmentFactory
            ->createWithData(
                AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT,
                'Discount when buying 3 pieces or more',
                -20,
                false,
                [
                    'priceTierId' => 99,
                    'priceTierQuantity' => 3,
                    'priceTierDiscount' => '10',
                ],
            )
            ->will(function () use (&$callIndex, $adjustments) {
                return $adjustments[$callIndex++]->reveal();
            })
            ->shouldBeCalledTimes(5)
        ;

        foreach ($units as $i => $unit) {
            $unit->addAdjustment(Argument::that(fn ($adjustment): bool => $adjustment === $adjustments[$i]->reveal()))
                ->shouldBeCalled()
            ;
        }

        $this->processor()->process($order->reveal());
    }

    #[Test]
    public function it_uses_ceiling_rounding_so_partial_cents_round_up(): void
    {
        // 999 × 10 / 100 = 99.9; ceiling rounding pushes the integer discount to 100, not 99.
        $variant = $this->prophesize(ProductVariantInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $priceTier = new PriceTier();
        $priceTier->setQuantity(2);
        $priceTier->setDiscount('10');

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel);

        $item = $this->prophesize(OrderItemInterface::class);
        $item->getVariant()->willReturn($variant);
        $item->getQuantity()->willReturn(2);
        $item->getTotal()->willReturn(999);
        $item->getUnits()->willReturn(new ArrayCollection([])); // skip the adjustment loop — we only care about the math

        $order->getItems()->willReturn(new ArrayCollection([$item->reveal()]));

        $this->priceTierProvider->getPriceTier(2, $variant, $channel)->willReturn($priceTier);

        $this->distributor->distribute(100, 2)->shouldBeCalled()->willReturn([50, 50]);

        $this->translator
            ->trans(Argument::cetera())
            ->willReturn('label')
        ;

        $this->processor()->process($order->reveal());
    }

    #[Test]
    public function it_stops_creating_adjustments_when_the_distributor_split_runs_out(): void
    {
        // A short split (1 non-zero followed by 0) indicates the distributor couldn't allocate evenly.
        // The processor breaks out instead of attaching zero-amount adjustments to the remaining units.
        $variant = $this->prophesize(ProductVariantInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $priceTier = new PriceTier();
        $priceTier->setQuantity(2);
        $priceTier->setDiscount('1');

        $units = $this->units(3);

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel);

        $item = $this->prophesize(OrderItemInterface::class);
        $item->getVariant()->willReturn($variant);
        $item->getQuantity()->willReturn(3);
        $item->getTotal()->willReturn(100);
        $item->getUnits()->willReturn(new ArrayCollection(array_map(static fn (ObjectProphecy $u) => $u->reveal(), $units)));

        $order->getItems()->willReturn(new ArrayCollection([$item->reveal()]));

        $this->priceTierProvider->getPriceTier(3, $variant, $channel)->willReturn($priceTier);
        $this->distributor->distribute(1, 3)->willReturn([1, 0, 0]);

        $this->translator->trans(Argument::cetera())->willReturn('label');

        $adjustment = $this->prophesize(AdjustmentInterface::class);
        $adjustment->setOriginCode('tier_pricing')->shouldBeCalled();

        $this->adjustmentFactory
            ->createWithData(Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn($adjustment->reveal())
        ;

        $units[0]->addAdjustment(Argument::any())->shouldBeCalled();
        $units[1]->addAdjustment(Argument::any())->shouldNotBeCalled();
        $units[2]->addAdjustment(Argument::any())->shouldNotBeCalled();

        $this->processor()->process($order->reveal());
    }

    /**
     * @return list<ObjectProphecy>
     */
    private function units(int $count): array
    {
        $units = [];
        for ($i = 0; $i < $count; ++$i) {
            $units[] = $this->prophesize(OrderItemUnitInterface::class);
        }

        return $units;
    }

    private function processor(): PriceTiersOrderProcessor
    {
        return new PriceTiersOrderProcessor(
            $this->priceTierProvider->reveal(),
            $this->adjustmentFactory->reveal(),
            $this->distributor->reveal(),
            $this->translator->reveal(),
        );
    }

    private function setPriceTierId(PriceTier $priceTier, int $id): void
    {
        $reflection = new \ReflectionProperty(PriceTier::class, 'id');
        $reflection->setValue($priceTier, $id);
    }
}
