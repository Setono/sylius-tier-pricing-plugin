# Upgrade from 1.x to 2.x

This release upgrades the plugin to Sylius 2. Most consumers only need to update their Composer constraints and re-run install — the plugin still ships the same `PriceTier` resource, the same `PriceTierProvider`, and the same `sylius.order_processor` (priority 15) that calculates per-unit `tier_pricing` adjustments.

## Requirements

| | 1.x | 2.x |
| --- | --- | --- |
| PHP | `>=8.1` | `>=8.2` |
| Symfony | `^5.4 \|\| ^6.4` | `^6.4 \|\| ^7.4` |
| Sylius | `~1.12.13` | `^2.0` |

## What changed

### Resource layout moved to repo root

Following the Sylius 2 plugin convention, all configuration moved out of `src/Resources/`:

| 1.x | 2.x |
| --- | --- |
| `src/Resources/config/services.xml` | `config/services.xml` |
| `src/Resources/config/services/*.xml` | `config/services/*.xml` |
| `src/Resources/config/routes.yaml` | `config/routes.yaml` |
| `src/Resources/config/routes/*.yaml` | `config/routes/*.yaml` |
| `src/Resources/config/doctrine/model/PriceTier.orm.xml` | `config/doctrine/model/PriceTier.orm.xml` |
| `src/Resources/config/validation/PriceTier.xml` | `config/validation/PriceTier.xml` |
| `src/Resources/translations/*.yaml` | `translations/*.yaml` |
| `src/Resources/views/admin/**/*.twig` | `templates/admin/**/*.twig` |

`SetonoSyliusTierPricingPlugin::getPath()` now returns `dirname(__DIR__)` so Sylius resolves these paths from the repository root. If you imported any of the plugin's YAML/Twig files via `@SetonoSyliusTierPricingPlugin/Resources/...` references, replace them with `@SetonoSyliusTierPricingPlugin/config/...` (or `/templates/...`).

### Admin product tab is now driven by Twig hooks

The v1 menu/template-event combo (`ProductFormMenuSubscriber` + the `tab_price_tiers` event) is gone — Sylius 2 has no `ProductMenuBuilderEvent`. The plugin now registers the price-tiers tab via `sylius_twig_hooks` configuration and ships these templates instead:

| 1.x template | 2.x template |
| --- | --- |
| `@SetonoSyliusTierPricingPlugin/admin/product/tab/_price_tiers.html.twig` | `@SetonoSyliusTierPricingPlugin/admin/product/form/sections/price_tiers.html.twig` |
| _(injected via menu subscriber)_ | `@SetonoSyliusTierPricingPlugin/admin/product/form/side_navigation/price_tiers.html.twig` |

Hooks registered (auto-loaded via the bundle's `prepend()` — no consumer-side import required):

- `sylius_admin.product.{update,create}.content.form.side_navigation` (the side-nav tab button)
- `sylius_admin.product.{update,create}.content.form.sections` (the panel that renders `form.priceTiers`)

If you customized either the old `tab/_price_tiers.html.twig` or the legacy `sylius.admin.product.{update,create}.tab_price_tiers` event, port those changes to one of the new hook templates and override them via the standard Sylius 2 template-resolver mechanism.

### Form extension stayed

`Setono\SyliusTierPricingPlugin\Form\Extension\ProductTypeExtension` is unchanged — it still adds the `priceTiers` field to the Sylius `ProductType`. Persisting tiers when the admin saves a product still goes through Sylius' resource controller as before. The hook templates above just place that already-extended form into the correct UI slots.

### Removed classes

- `Setono\SyliusTierPricingPlugin\EventSubscriber\ProductFormMenuSubscriber` — replaced by the Twig hook config above.

### Tooling

- Psalm replaced by PHPStan (`composer analyse` now runs `phpstan analyse`).
- `setono/code-quality-pack` replaced by `setono/sylius-plugin` (provides PHPStan + ECS + Rector + composer-dependency-analyser + the GitHub composite actions used by `.github/workflows/build.yaml`).
- CI rewritten on top of `setono/sylius-plugin/<job>@v2` composite actions; the matrix now covers PHP 8.2/8.3/8.4 × Symfony 6.4/7.4 × lowest/highest deps.
