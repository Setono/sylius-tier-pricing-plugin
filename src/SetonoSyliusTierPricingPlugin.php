<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin;

use Setono\SyliusTierPricingPlugin\DependencyInjection\Compiler\RegisterPriceTierProviderPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SetonoSyliusTierPricingPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterPriceTierProviderPass());
    }
}
