<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Webmozart\Assert\Assert;

final class SetonoSyliusTierPricingExtension extends AbstractResourceExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         *
         * @var array{resources: array<string, mixed>, adjustment_origin_code: ?string} $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        if (isset($config['adjustment_origin_code'])) {
            $originCode = $config['adjustment_origin_code'];
            Assert::stringNotEmpty($originCode, 'If provided, adjustment_origin_code must be a non-empty string');
            $container->setParameter('setono_sylius_tier_pricing.adjustment_origin_code', $originCode);
        } else {
            $container->setParameter('setono_sylius_tier_pricing.adjustment_origin_code', null);
        }

        $this->registerResources(
            'setono_sylius_tier_pricing',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );

        $loader->load('services.xml');
    }
}
