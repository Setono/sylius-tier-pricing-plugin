<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\DependencyInjection\Compiler;

use Setono\SyliusTierPricingPlugin\Model\PriceTiersAwareInterface;
use Setono\SyliusTierPricingPlugin\Provider\PriceTierProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function Symfony\Component\String\u;

final class RegisterPriceTierProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sylius.resources')) {
            return;
        }

        /** @var array<string, array{classes: array{model: class-string}}> $resources */
        $resources = $container->getParameter('sylius.resources');

        /** @var list<string> $priceTiersAwareResources */
        $priceTiersAwareResources = [];

        foreach ($resources as $resourceName => $resource) {
            if (!isset($resource['classes']['model'])) {
                continue;
            }

            $model = $resource['classes']['model'];

            if (!is_a($model, PriceTiersAwareInterface::class, true)) {
                continue;
            }

            $priceTiersAwareResources[] = $resourceName;
        }

        if ([] === $priceTiersAwareResources) {
            throw new \InvalidArgumentException(sprintf(
                'You need to have one resource implementing %s',
                PriceTiersAwareInterface::class,
            ));
        }

        if (count($priceTiersAwareResources) > 1) {
            throw new \InvalidArgumentException(sprintf(
                'You can only have one resource implementing %s',
                PriceTiersAwareInterface::class,
            ));
        }

        $priceTiersAwareResource = $priceTiersAwareResources[0];

        $eligiblePriceTiersAwareResources = ['sylius.product', 'sylius.product_variant', 'sylius.channel_pricing'];
        if (!in_array($priceTiersAwareResource, $eligiblePriceTiersAwareResources, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The resource implementing %s must be one of [%s]',
                PriceTiersAwareInterface::class,
                implode(', ', $eligiblePriceTiersAwareResources),
            ));
        }

        $container->setAlias(PriceTierProviderInterface::class, 'setono_sylius_tier_pricing.provider.price_tier.default');
        $container->setAlias('setono_sylius_tier_pricing.provider.price_tier.default', sprintf(
            'setono_sylius_tier_pricing.provider.price_tier.%s_based',
            u($priceTiersAwareResource)->trimPrefix('sylius.')->toString(),
        ));
    }
}
