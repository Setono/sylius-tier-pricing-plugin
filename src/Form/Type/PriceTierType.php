<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Form\Type;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            'scale' => 7, // defined in config/doctrine/model/PriceTier.orm.xml
            'help' => 'setono_sylius_tier_pricing.form.price_tier.discount_help',
        ])->add('channel', ChannelChoiceType::class, [
            'label' => 'sylius.ui.channel',
            'required' => false,
        ]);

        $product = $options['product'];
        if ($product instanceof ProductInterface) {
            $builder->add('productVariant', ProductVariantChoiceType::class, [
                'label' => 'sylius.ui.variant',
                'product' => $product,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault('product', null)
            ->setAllowedTypes('product', [ProductInterface::class, 'null'])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_tier_pricing_price_tier';
    }
}
