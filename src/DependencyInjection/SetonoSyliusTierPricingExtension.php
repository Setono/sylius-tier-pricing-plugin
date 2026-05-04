<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class SetonoSyliusTierPricingExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{resources: array<string, mixed>} $config */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $this->registerResources(
            'setono_sylius_tier_pricing',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_twig_hooks')) {
            return;
        }

        /** @var array{sylius_twig_hooks?: array<string, mixed>} $config */
        $config = Yaml::parseFile(__DIR__ . '/../../config/sylius_twig_hooks.yaml');

        $container->prependExtensionConfig('sylius_twig_hooks', $config['sylius_twig_hooks'] ?? []);
    }
}
