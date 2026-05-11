<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusTierPricingPlugin\Form\Extension\ProductTypeExtension;
use Setono\SyliusTierPricingPlugin\Form\Type\PriceTierType;

return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $parameters->set('setono_sylius_tier_pricing.form.type.price_tier.validation_groups', ['setono_sylius_tier_pricing']);

    $services = $container->services();

    $services->set(PriceTierType::class)
        ->args([
            param('setono_sylius_tier_pricing.model.price_tier.class'),
            param('setono_sylius_tier_pricing.form.type.price_tier.validation_groups'),
        ])
        ->tag('form.type');

    $services->set(ProductTypeExtension::class)
        ->tag('form.type_extension');
};
