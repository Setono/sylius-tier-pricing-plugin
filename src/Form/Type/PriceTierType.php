<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Form\Type;

use Setono\SyliusTierPricingPlugin\Model\PriceTierInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Webmozart\Assert\Assert;

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
        ])->add('channel', ChannelChoiceType::class, [
            'label' => 'sylius.ui.channel',
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event): void {
            /** @var PriceTierInterface|null $priceTier */
            $priceTier = $event->getData();
            Assert::nullOrIsInstanceOf($priceTier, PriceTierInterface::class);

            if (null === $priceTier) {
                return;
            }

            $product = $priceTier->getProduct();
            if (null === $product) {
                return;
            }

            $event->getForm()->add('productVariant', ProductVariantChoiceType::class, [
                'label' => 'sylius.ui.variant',
                'product' => $product,
                'required' => false,
            ]);
        });
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_tier_pricing_price_tier';
    }
}
