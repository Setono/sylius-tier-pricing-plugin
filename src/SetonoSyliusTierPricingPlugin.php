<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;

/**
 * Pure AbstractResourceBundle wiring — getPath()/getConfigFilesPath()/getSupportedDrivers() are exercised
 * implicitly every time the container boots, but they're not meaningful units to assert on in isolation.
 * Skip them in coverage so the report stays focused on logic that benefits from per-line scrutiny.
 *
 * @codeCoverageIgnore
 */
final class SetonoSyliusTierPricingPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

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
