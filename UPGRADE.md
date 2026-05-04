# Upgrade from 1.x to 2.x

This release upgrades the plugin to Sylius 2. Most consumers only need to update their Composer constraints and re-run install — the plugin still ships the same `PriceTier` resource, the same `PriceTierProvider`, and the same `sylius.order_processor` (priority 15) that calculates per-unit `tier_pricing` adjustments.

## Requirements

| | 1.x | 2.x |
| --- | --- | --- |
| PHP | `>=8.1` | `>=8.2` |
| Symfony | `^5.4 \|\| ^6.4` | `^6.4 \|\| ^7.4` |
| Sylius | `~1.12.13` | `^2.0` |

The plugin is tested against PHP 8.2/8.3/8.4, Symfony 6.4 and 7.4, lowest and highest deps.

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

`SetonoSyliusTierPricingPlugin::getPath()` now returns `dirname(__DIR__)` so Sylius resolves these paths from the repository root. **Additionally**, `SetonoSyliusTierPricingPlugin::getConfigFilesPath()` is overridden — without it, `AbstractResourceBundle` would still look for the Doctrine mapping under `<root>/Resources/config/doctrine/model/` and bootstrap would fail with `File mapping drivers must have a valid directory path`.

If you imported any of the plugin's YAML/Twig files via `@SetonoSyliusTierPricingPlugin/Resources/...` references, replace them with `@SetonoSyliusTierPricingPlugin/config/...` (or `/templates/...`).

### Admin product tab is now driven by Twig hooks

The v1 menu/template-event combo (`ProductFormMenuSubscriber` + the `tab_price_tiers` event) is gone — Sylius 2 has no `ProductMenuBuilderEvent`. The plugin now registers the price-tiers tab via `sylius_twig_hooks` configuration and ships these templates instead:

| 1.x template | 2.x template |
| --- | --- |
| `@SetonoSyliusTierPricingPlugin/admin/product/tab/_price_tiers.html.twig` | `@SetonoSyliusTierPricingPlugin/admin/product/form/sections/price_tiers.html.twig` |
| _(injected via menu subscriber)_ | `@SetonoSyliusTierPricingPlugin/admin/product/form/side_navigation/price_tiers.html.twig` |

Hooks registered (auto-loaded via the bundle's `prepend()` — no consumer-side import required):

- `sylius_admin.product.{update,create}.content.form.side_navigation` (the side-nav tab button)
- `sylius_admin.product.{update,create}.content.form.sections` (the panel that renders the price-tiers form)

If you customized either the old `tab/_price_tiers.html.twig` or the legacy `sylius.admin.product.{update,create}.tab_price_tiers` event, port those changes to one of the new hook templates and override them via the standard Sylius 2 template-resolver mechanism.

### Add/Delete now driven by Symfony UX Live Components

`Form\Type\PriceTierCollectionType::getParent()` returns `Symfony\UX\LiveComponent\Form\Type\LiveCollectionType` instead of `Symfony\Component\Form\Extension\Core\Type\CollectionType`. The Add and Delete buttons in the admin tab fire server-side `addCollectionItem` / `removeCollectionItem` actions — same pattern Sylius admin uses for product images. There is no client-side prototype-cloning JS shipped by the plugin.

To make the round-trip work, `PriceTierType` now sets `empty_data` on the required scalar fields (`quantity` → `'1'`, `discount` → `'0.0'`). Without these, the Live Component's empty-binding cycle on `addCollectionItem` would call `PriceTier::setQuantity(null)` and throw `InvalidTypeException`. Consumers don't need to do anything.

The plugin's add/delete only works when it's hooked into a form that is itself a Live Component. Sylius admin's product form already is (`component: 'sylius_admin:product:form'` in the core's `twig_hooks/product/update.yaml`), so this works out of the box; if you embed `PriceTierCollectionType` somewhere else, you need to expose that form as a Live Component or fall back to a custom Stimulus controller.

### Form extension stayed

`Setono\SyliusTierPricingPlugin\Form\Extension\ProductTypeExtension` is unchanged in shape — it still adds the `priceTiers` field to the Sylius `ProductType`. Persisting tiers when the admin saves a product still goes through Sylius' resource controller as before. The hook templates above place that already-extended form into the correct UI slots.

### Doctrine ORM 3 — attribute mappings only

Sylius 2 pulls in Doctrine ORM 3, which silently ignores `@ORM\*` PHPDoc tags on attribute-mapped entities. `Model\ProductTrait` previously declared the `priceTiers` collection via `@ORM\OneToMany` PHPDoc; it now uses `#[ORM\OneToMany(...)]` attribute. If your `App\Entity\Product` is attribute-mapped (the Sylius 2 default), no action needed — the trait's attribute travels with it. If your project is still on XML/YAML mapping for `Product`, you will need to add the inverse-side mapping yourself.

### Service-id renames affecting the order processor

- `sylius.integer_distributor` → `sylius.distributor.integer`. Already updated in `config/services/order_processor.xml`. If you injected the plugin's order processor manually anywhere, double-check the constructor signature still resolves.

### Removed classes

- `Setono\SyliusTierPricingPlugin\EventSubscriber\ProductFormMenuSubscriber` — replaced by the Twig hook config above.

### Tooling

- Psalm replaced by PHPStan (level max). `composer analyse` runs `phpstan analyse`. `psalm.xml` is removed; `phpstan.neon` is the new config (with Symfony + Doctrine + PHPUnit + strict-rules + Prophecy plugins via `setono/sylius-plugin`).
- `setono/code-quality-pack` replaced by `setono/sylius-plugin: ^2.0` — provides PHPStan + ECS + Rector + `shipmonk/composer-dependency-analyser` + the GitHub composite actions used by `.github/workflows/build.yaml`.
- `composer-dependency-analyser.php` replaces `composer-require-checker.json` + `composer-unused.php`.
- CI rewritten on top of `setono/sylius-plugin/<job>@v2` composite actions; the matrix now covers PHP 8.2/8.3/8.4 × Symfony 6.4/7.4 × lowest/highest deps. The standalone backwards-compatibility-check workflow folded into `build.yaml`.

### Implicit nullable parameter signatures

Plugin-public method signatures changed from `Foo $x = null` to `?Foo $x = null` (PHP 8.4 deprecates implicit nullables). Only `PriceTierProviderInterface::getPriceTier()` / `getPriceTiers()` are technically affected; behaviour is identical.
