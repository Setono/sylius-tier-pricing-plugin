<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;

final class SetonoSyliusTierPricingPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    protected function getConfigFilesPath(): string
    {
        return sprintf('%s/config/doctrine/%s', $this->getPath(), strtolower($this->getDoctrineMappingDirectory()));
    }
}
