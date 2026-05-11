<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\DependencyInjection;

use Setono\SyliusTierPricingPlugin\Model\PriceTier;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /** @phpstan-ignore missingType.generics (TreeBuilder is generic in Symfony >=7.1 but not in 6.4) */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_tier_pricing');

        /**
         * @var ArrayNodeDefinition $rootNode
         *
         * @phpstan-ignore varTag.generics (ArrayNodeDefinition is generic in Symfony >=7.1 but not in 6.4)
         */
        $rootNode = $treeBuilder->getRootNode();

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    /** @phpstan-ignore missingType.generics (ArrayNodeDefinition is generic in Symfony >=7.1 but not in 6.4) */
    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        /** @psalm-suppress MixedMethodCall,PossiblyNullReference,UndefinedInterfaceMethod */
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('price_tier')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('model')->defaultValue(PriceTier::class)->cannotBeEmpty()->end()
                                            ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                            ->scalarNode('repository')->cannotBeEmpty()->end()
                                            ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                            ->scalarNode('form')->cannotBeEmpty()->end()
        ;
    }
}
