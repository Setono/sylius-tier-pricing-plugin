<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Form\Extension;

use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierCollectionType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

final class ProductTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['data'] ?? null;

        $builder->add('priceTiers', PriceTierCollectionType::class, [
            'label' => false,
            'constraints' => [new Valid()],
            'entry_options' => [
                // Forward the parent product down so each PriceTierType (including freshly-added rows the
                // user hasn't yet linked) can render the variant-selection field for *this* product.
                'product' => $product instanceof ProductInterface ? $product : null,
            ],
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}
