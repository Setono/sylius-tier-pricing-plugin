<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @extends AbstractType<mixed>
 */
final class PriceTierCollectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'entry_type' => PriceTierType::class,
                'entry_options' => [],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'block_name' => 'entry',
                'button_add_options' => [
                    'label' => 'setono_sylius_tier_pricing.ui.add_price_tier',
                ],
            ])
            ->setNormalizer(
                'entry_options',
                // Always set label=false; merge with anything the caller passed (e.g. `product` from ProductTypeExtension).
                static fn (Options $options, mixed $value): array => array_merge(['label' => false], is_array($value) ? $value : []),
            )
        ;
    }

    public function getParent(): string
    {
        return LiveCollectionType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_tier_pricing_price_tier_collection';
    }
}
