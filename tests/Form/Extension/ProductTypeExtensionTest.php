<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Form\Extension;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusTierPricingPlugin\Form\Extension\ProductTypeExtension;
use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierCollectionType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

final class ProductTypeExtensionTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_extends_the_sylius_product_form_type(): void
    {
        self::assertSame([ProductType::class], iterator_to_array(
            (function (): \Generator {
                yield from ProductTypeExtension::getExtendedTypes();
            })(),
        ));
    }

    #[Test]
    public function it_adds_the_price_tiers_field_with_a_valid_constraint_and_no_label(): void
    {
        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder
            ->add(
                'priceTiers',
                PriceTierCollectionType::class,
                Argument::that(static function (array $options): bool {
                    if (false !== $options['label']) {
                        return false;
                    }

                    if (!isset($options['constraints']) || !\is_array($options['constraints'])) {
                        return false;
                    }

                    return 1 === count($options['constraints']) && $options['constraints'][0] instanceof Valid;
                }),
            )
            ->shouldBeCalledOnce()
            ->willReturn($builder->reveal());

        (new ProductTypeExtension())->buildForm($builder->reveal(), []);
    }
}
