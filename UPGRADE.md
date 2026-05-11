# Upgrade from 1.x to 2.x

For most consumers the upgrade is two steps: bump the Composer constraints and re-run install. The plugin still ships the same `PriceTier` resource, the same `PriceTierProvider`, and the same `sylius.order_processor` (priority 15) that calculates per-unit `tier_pricing` adjustments.

## Requirements

| | 1.x | 2.x |
| --- | --- | --- |
| PHP | `>=8.1` | `>=8.2` |
| Symfony | `^5.4 \|\| ^6.4` | `^6.4 \|\| ^7.4` |
| Sylius | `~1.12.13` | `^2.0` |

## What you may need to change

### `@SetonoSyliusTierPricingPlugin/Resources/...` references

The plugin's config and templates moved out of `src/Resources/`. If you imported any of them via the bundle alias, replace `@SetonoSyliusTierPricingPlugin/Resources/...` with `@SetonoSyliusTierPricingPlugin/config/...` (or `/templates/...`).

### Admin product tab template overrides

The v1 menu/template-event combo (`ProductFormMenuSubscriber` + the `sylius.admin.product.{update,create}.tab_price_tiers` event) is gone — Sylius 2 has no `ProductMenuBuilderEvent`. The plugin now registers the tab via `sylius_twig_hooks` configuration. If you overrode either of these templates, port your changes to the new locations and override via the standard Sylius 2 template-resolver mechanism:

| 1.x template | 2.x template |
| --- | --- |
| `@SetonoSyliusTierPricingPlugin/admin/product/tab/_price_tiers.html.twig` | `@SetonoSyliusTierPricingPlugin/admin/product/form/sections/price_tiers.html.twig` |
| _(injected via menu subscriber)_ | `@SetonoSyliusTierPricingPlugin/admin/product/form/side_navigation/price_tiers.html.twig` |

Hook points the plugin attaches to (in case you want to add your own partials alongside ours):

- `sylius_admin.product.{update,create}.content.form.side_navigation`
- `sylius_admin.product.{update,create}.content.form.sections`

### `App\Entity\Product` mapping format

`Model\ProductTrait::priceTiers` is now mapped via a `#[ORM\OneToMany]` attribute (Doctrine ORM 3 dropped PHPDoc annotation support). If your `App\Entity\Product` uses attribute mapping (the Sylius 2 default) no action is needed — the trait's attribute travels with it. If you map `Product` via XML or YAML, you must add the inverse-side `priceTiers` association in your own mapping file.

### Embedding `PriceTierCollectionType` outside the admin product form

The collection type now extends `Symfony\UX\LiveComponent\Form\Type\LiveCollectionType` so add/delete fire server-side via Symfony UX Live Components. This works automatically inside the admin product form (which is itself a Live Component). If you embed `PriceTierCollectionType` in a custom form somewhere else, that parent form also needs to be a Live Component for add/delete to work — otherwise the buttons render but their actions don't dispatch.

### `PriceTierType` now takes a `product` option instead of inferring from initial data

In 1.x the `productVariant` field was added by a `PRE_SET_DATA` listener that read `$priceTier->getProduct()`. In 2.x, freshly-added rows in a `LiveCollectionType` are built *before* the parent assigns the backreference, so the listener pattern fails silently on new rows. The variant field is now added unconditionally when an optional `product` form option (typed `ProductInterface|null`) is set, and `ProductTypeExtension` forwards the parent product down via `entry_options.product`. If you instantiate `PriceTierType` directly (rare — the bundled `ProductTypeExtension` covers the admin case), pass `'product' => $product` in the options array.

### Service-id renames

DI service configuration moved from XML to the PHP DSL, and every service id the plugin owns switched from a snake-case alias to its FQCN. The interface gets an alias so consumers fetching by interface keep working. If you injected the plugin's services by snake-case id (in your own YAML, XML, decorators, etc.), update the reference.

| 1.x service id | 2.x service id |
| --- | --- |
| `setono_sylius_tier_pricing.provider.price_tier` | `Setono\SyliusTierPricingPlugin\Provider\PriceTierProvider` (plus `Setono\SyliusTierPricingPlugin\Provider\PriceTierProviderInterface` alias) |
| `setono_sylius_tier_pricing.order_processor.price_tiers` | `Setono\SyliusTierPricingPlugin\OrderProcessor\PriceTiersOrderProcessor` |
| `setono_sylius_tier_pricing.form.type.price_tier` | `Setono\SyliusTierPricingPlugin\Form\Type\PriceTierType` |
| `setono_sylius_tier_pricing.form.extension.product` | `Setono\SyliusTierPricingPlugin\Form\Extension\ProductTypeExtension` |

The parameter `setono_sylius_tier_pricing.form.type.price_tier.validation_groups` is unchanged. The Sylius-resource-bundle-managed ids (`setono_sylius_tier_pricing.factory.price_tier`, `setono_sylius_tier_pricing.repository.price_tier`, etc.) are also unchanged — they're auto-generated from the resource config.

The plugin's order processor previously injected `sylius.integer_distributor`; Sylius 2 renamed that to `sylius.distributor.integer`. Already done inside the plugin — only relevant if you copied the constructor signature into your own wiring.

### Removed

- `Setono\SyliusTierPricingPlugin\EventSubscriber\ProductFormMenuSubscriber` — the menu-builder pattern doesn't exist in v2.

### Public-API signature changes

- `PriceTierProviderInterface::getPriceTier()` and `getPriceTiers()` changed `ChannelInterface $channel = null` → `?ChannelInterface $channel = null` (PHP 8.4 deprecates implicit nullables). Behaviour is identical.
- `PriceTierInterface::setQuantity()` changed `int $quantity` → `?int $quantity`. Passing null is a no-op (leaves the existing value untouched).
- `PriceTierInterface::setDiscount()` changed `float|string $discount` → `float|string|null $discount`. Same no-op-on-null semantics.

The setter changes let `PriceTier` survive `Symfony\UX\LiveComponent\Form\Type\LiveCollectionType`'s empty-bind cycle, which posts `null` for every required field on a freshly-added row before the user types anything. If you implemented `PriceTierInterface` yourself, widen your signatures to match.
