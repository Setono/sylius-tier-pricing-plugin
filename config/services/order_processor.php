<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusTierPricingPlugin\OrderProcessor\PriceTiersOrderProcessor;
use Setono\SyliusTierPricingPlugin\Provider\PriceTierProvider;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(PriceTiersOrderProcessor::class)
        ->args([
            service(PriceTierProvider::class),
            service('sylius.factory.adjustment'),
            service('sylius.distributor.integer'),
            service('translator'),
        ])
        ->tag('sylius.order_processor', ['priority' => 15]);
};
