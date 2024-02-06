<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Application\Model;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusTierPricingPlugin\Model\ProductInterface;
use Setono\SyliusTierPricingPlugin\Model\ProductTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

/**
 * @ORM\Table(name="sylius_product")
 *
 * @ORM\Entity()
 */
class Product extends BaseProduct implements ProductInterface
{
    use ProductTrait {
        ProductTrait::__construct as private _initializePriceTiers;
    }

    public function __construct()
    {
        parent::__construct();

        $this->_initializePriceTiers();
    }
}
