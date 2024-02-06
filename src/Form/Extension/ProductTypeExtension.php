<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Form\Extension;

use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierCollectionType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('priceTiers', PriceTierCollectionType::class, [
            'label' => false,
        ]);
    }

    public static function getExtendedTypes(): \Generator
    {
        yield ProductType::class;
    }
}
