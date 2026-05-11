<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusTierPricingPlugin\DependencyInjection\SetonoSyliusTierPricingExtension;

final class SetonoSyliusTierPricingExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusTierPricingExtension(),
        ];
    }

    #[Test]
    public function after_loading_the_correct_parameter_has_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_tier_pricing.model.price_tier.class');
    }
}
