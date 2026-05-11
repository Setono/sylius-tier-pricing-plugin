<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class SetonoSyliusTierPricingExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{resources: array<string, mixed>} $config */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $this->registerResources(
            'setono_sylius_tier_pricing',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );

        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_twig_hooks')) {
            return;
        }

        $sideNavigationTemplate = '@SetonoSyliusTierPricingPlugin/admin/product/form/side_navigation/price_tiers.html.twig';
        $sectionsTemplate = '@SetonoSyliusTierPricingPlugin/admin/product/form/sections/price_tiers.html.twig';

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                'sylius_admin.product.update.content.form.side_navigation' => [
                    'price_tiers' => [
                        'template' => $sideNavigationTemplate,
                        'priority' => -100,
                    ],
                ],
                'sylius_admin.product.create.content.form.side_navigation' => [
                    'price_tiers' => [
                        'template' => $sideNavigationTemplate,
                        'priority' => -100,
                    ],
                ],
                'sylius_admin.product.update.content.form.sections' => [
                    'price_tiers' => [
                        'template' => $sectionsTemplate,
                        'priority' => -100,
                    ],
                ],
                'sylius_admin.product.create.content.form.sections' => [
                    'price_tiers' => [
                        'template' => $sectionsTemplate,
                        'priority' => -100,
                    ],
                ],
            ],
        ]);
    }
}
