<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

final class PriceTierType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('quantity', IntegerType::class, [
            'label' => 'setono_sylius_tier_pricing.form.price_tier.quantity',
        ])->add('discount', NumberType::class, [
            'label' => 'setono_sylius_tier_pricing.form.price_tier.discount',
            'html5' => true,
            'input' => 'string',
            'scale' => 7, // defined in src/Resources/config/doctrine/model/PriceTier.orm.xml
            'help' => 'setono_sylius_tier_pricing.form.price_tier.discount_help',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_tier_pricing_price_tier';
    }
}
