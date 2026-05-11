# Sylius Tier Pricing Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

Use quantity-based price tiers in your Sylius store. Set a tier like "10% off when buying 5 or more" on a product, optionally scoped to a specific channel and/or variant; the discount is applied automatically as a per-unit `ORDER_UNIT_PROMOTION_ADJUSTMENT` with `originCode = 'tier_pricing'`.

## Requirements

- PHP `>=8.2`
- Symfony `^6.4 || ^7.4`
- Sylius `^2.0`

For Sylius 1.x see the [`1.x`](https://github.com/Setono/sylius-tier-pricing-plugin/tree/1.x) branch. Upgrading from 1.x → 2.x? See [UPGRADE.md](UPGRADE.md).

## Installation

```bash
composer require setono/sylius-tier-pricing-plugin
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Setono\SyliusTierPricingPlugin\SetonoSyliusTierPricingPlugin::class => ['all' => true],
];
```

Make your `App\Entity\Product` use the plugin's trait + interface:

```php
use Setono\SyliusTierPricingPlugin\Model\ProductInterface as TierPricingProductInterface;
use Setono\SyliusTierPricingPlugin\Model\ProductTrait as TierPricingProductTrait;

class Product extends BaseProduct implements TierPricingProductInterface
{
    use TierPricingProductTrait {
        TierPricingProductTrait::__construct as private _initializePriceTiers;
    }

    public function __construct()
    {
        parent::__construct();
        $this->_initializePriceTiers();
    }
}
```

Wire the resource override in `config/packages/_sylius.yaml`:

```yaml
sylius_product:
    resources:
        product:
            classes:
                model: App\Entity\Product
```

Update the database schema:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

## How it works

After install, products in the admin get a **Price tiers** tab (rendered via Twig hooks into the product update/create page). Each tier has:

- **Quantity** — minimum units the customer must add to the cart for the tier to kick in.
- **Discount** — percentage off, kept as a numeric string and computed with `brick/math` to avoid float drift.
- **Channel** — optional; restricts the tier to one channel.
- **Variant** — optional; restricts the tier to one variant of the product.

When an order is processed, the `PriceTiersOrderProcessor` (priority 15, runs *before* tax/shipping) picks the best-matching tier per item using the precedence `(channel + variant) > variant > channel > generic`, computes the discount with `RoundingMode::CEILING`, distributes it across units via `sylius.distributor.integer`, and attaches one adjustment per unit.

[ico-version]: https://poser.pugx.org/setono/sylius-tier-pricing-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-tier-pricing-plugin/license
[ico-github-actions]: https://github.com/Setono/sylius-tier-pricing-plugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/sylius-tier-pricing-plugin/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2Fsylius-tier-pricing-plugin%2F2.x

[link-packagist]: https://packagist.org/packages/setono/sylius-tier-pricing-plugin
[link-github-actions]: https://github.com/Setono/sylius-tier-pricing-plugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/sylius-tier-pricing-plugin
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/sylius-tier-pricing-plugin/2.x
