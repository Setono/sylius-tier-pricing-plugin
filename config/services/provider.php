<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusTierPricingPlugin\Provider\PriceTierProvider;
use Setono\SyliusTierPricingPlugin\Provider\PriceTierProviderInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(PriceTierProvider::class)
        ->args([service('sylius.context.channel')]);

    $services->alias(PriceTierProviderInterface::class, PriceTierProvider::class);
};
