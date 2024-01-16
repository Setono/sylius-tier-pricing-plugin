<?php
declare(strict_types=1);

namespace Tests\Setono\SyliusTierPricingPlugin\Application\Model;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusTierPricingPlugin\Model\PriceTiersAwareInterface;
use Setono\SyliusTierPricingPlugin\Model\PriceTiersAwareTrait;
use Sylius\Component\Core\Model\ChannelPricing as BaseChannelPricing;

/**
 * @ORM\Table(name="sylius_channel_pricing")
 * @ORM\Entity()
 */
class ChannelPricing extends BaseChannelPricing implements PriceTiersAwareInterface
{
    use PriceTiersAwareTrait {
        PriceTiersAwareTrait::__construct as private __priceTiersAwareTraitConstruct;
    }

    public function __construct()
    {
        parent::__construct();

        $this->__priceTiersAwareTraitConstruct();
    }
}
