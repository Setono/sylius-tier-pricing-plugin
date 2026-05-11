<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Form\Extension;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusTierPricingPlugin\Form\Extension\ProductTypeExtension;
use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierCollectionType;
use Setono\SyliusTierPricingPlugin\Tests\Model\Fixture\ProductTraitFixture;
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
    public function it_adds_the_price_tiers_field_with_no_label_a_valid_constraint_and_the_parent_product_forwarded(): void
    {
        $product = new ProductTraitFixture();

        $captured = $this->captureAddCall(['data' => $product]);

        self::assertSame('priceTiers', $captured['name']);
        self::assertSame(PriceTierCollectionType::class, $captured['type']);

        $options = $captured['options'];
        self::assertFalse($options['label']);

        $constraints = $options['constraints'];
        self::assertIsArray($constraints);
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Valid::class, $constraints[0]);

        $entryOptions = $options['entry_options'];
        self::assertIsArray($entryOptions);
        self::assertSame($product, $entryOptions['product']);
    }

    #[Test]
    public function it_passes_null_product_when_the_parent_form_has_no_data_yet_eg_on_a_create_page(): void
    {
        $captured = $this->captureAddCall([]);

        $entryOptions = $captured['options']['entry_options'];
        self::assertIsArray($entryOptions);
        self::assertArrayHasKey('product', $entryOptions);
        self::assertNull($entryOptions['product']);
    }

    /**
     * @param array<string, mixed> $formOptions
     *
     * @return array{name: string, type: string, options: array<string, mixed>}
     */
    private function captureAddCall(array $formOptions): array
    {
        /** @var array{name: string, type: string, options: array<string, mixed>}|null $captured */
        $captured = null;
        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder
            ->add(Argument::cetera())
            ->shouldBeCalledOnce()
            ->will(function (array $args) use (&$captured, $builder): FormBuilderInterface {
                $name = $args[0];
                $type = $args[1];
                /** @var array<string, mixed> $options */
                $options = $args[2];
                assert(is_string($name));
                assert(is_string($type));

                $captured = ['name' => $name, 'type' => $type, 'options' => $options];

                return $builder->reveal();
            });

        (new ProductTypeExtension())->buildForm($builder->reveal(), $formOptions);

        self::assertNotNull($captured);

        return $captured;
    }
}
