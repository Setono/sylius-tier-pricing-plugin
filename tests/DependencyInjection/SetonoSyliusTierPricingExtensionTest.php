<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusTierPricingPlugin\DependencyInjection\SetonoSyliusTierPricingExtension;

/**
 * See examples of tests and configuration options here: https://github.com/SymfonyTest/SymfonyDependencyInjectionTest
 */
final class SetonoSyliusTierPricingExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusTierPricingExtension(),
        ];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_parameter_has_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_tier_pricing.model.price_tier.class');
        $this->assertContainerBuilderHasParameter('setono_sylius_tier_pricing.adjustment_origin_code');
    }
}
