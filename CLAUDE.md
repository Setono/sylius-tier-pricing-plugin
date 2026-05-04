# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Sylius plugin (`setono/sylius-tier-pricing-plugin`) that adds quantity-based price tiers to Sylius. PHP 8.2+, Symfony 6.4/7.4, Sylius ~2.2.5. Source lives in `src/`, namespace `Setono\SyliusTierPricingPlugin\`. Plugin layout follows the Sylius 2 convention — `config/`, `templates/`, `translations/` live at the repo root (not under `src/Resources/`). A full Sylius test app is embedded in `tests/Application/` and used as the kernel for PHPUnit + DI tests.

## Commands

Composer scripts (also available as zsh aliases from `~/CLAUDE.md`: `cf`/`ca`/`cfca`):

- `composer phpunit` — run the test suite (`composer phpunit -- --filter PriceTierProviderTest` for a single test).
- `composer analyse` — PHPStan (level max, config in `phpstan.neon`).
- `composer check-style` / `composer fix-style` — ECS using sylius-labs ruleset (`ecs.php`).
- `vendor/bin/rector process --dry-run` — Rector check (kept advisory in CI; PHP 8.2 levelset).
- `vendor/bin/infection` — mutation testing (`infection.json.dist`).
- `vendor/bin/composer-dependency-analyser` — dependency hygiene check (`composer-dependency-analyser.php`).

Test-app console (must `cd tests/Application` first — it's its own Symfony app with its own `composer.json` and `Kernel.php`):

- `bin/console lint:container` / `bin/console lint:yaml ../../config` / `bin/console lint:twig ../../templates`
- `bin/console doctrine:database:create --if-not-exists` then `bin/console doctrine:schema:create` to spin up the integration DB. CI uses MySQL/MariaDB at the URL declared in `tests/Application/.env`.
- For a full local browser-driven smoke test: also run `yarn install && yarn build` (Webpack Encore builds `public/build/{admin,shop}/`), `bin/console assets:install public --symfonkylink`, `bin/console lexik:jwt:generate-keypair --skip-if-exists`, `bin/console sylius:fixtures:load default --no-interaction`, then `symfony serve -d` from `tests/Application/`.

The repo root `./init` script is a one-shot rename tool inherited from the Setono plugin skeleton; do not run it on this repo. (It was deleted in the v1.x branch — re-check before touching.)

## Architecture

Three collaborating pieces drive tier pricing. Reading them together is the fastest way to understand the plugin:

1. **`Model\PriceTier`** — Doctrine entity owning `quantity`, `discount` (stored as string for `brick/math` precision), and optional `product` / `productVariant` / `channel` scoping. `setProductVariant()` always syncs `product` from the variant; `setProduct()` clears a variant that doesn't belong to the new product (invariant: a tier's variant must be one of its product's variants). The `Model\ProductTrait` adds the `priceTiers` collection to the Sylius `Product` via a `#[ORM\OneToMany]` attribute (Doctrine ORM 3 dropped PHPDoc annotations) and is mixed into the test app's `App\Entity\Product` (`tests/Application/Entity/Product.php`).

2. **`Provider\PriceTierProvider`** — given a quantity + variant + channel, returns the single best-matching tier. It groups all of the product's tiers by quantity, then for each quantity picks the most-specific tier using this precedence: `(channel + variant) > variant > channel > generic`. The result is sorted ascending by quantity (`ksort`, see `src/Provider/PriceTierProvider.php:67` — added because tiers can come out of the DB unordered), and `getPriceTier()` walks that list to find the highest quantity threshold the requested quantity meets. Channel defaults to `ChannelContextInterface` when not passed.

3. **`OrderProcessor\PriceTiersOrderProcessor`** — tagged `sylius.order_processor` with **priority 15** (runs *before* tax/shipping; `config/services/order_processor.xml`). For each order item it asks the provider for a tier, computes `total * discount%` rounded with `RoundingMode::CEILING`, splits the integer discount across units via Sylius' `sylius.distributor.integer` (renamed in v2 from `sylius.integer_distributor`), and attaches one `ORDER_UNIT_PROMOTION_ADJUSTMENT` per unit with `originCode = 'tier_pricing'` and metadata (`priceTierId`, `priceTierQuantity`, `priceTierDiscount`). It deliberately does **not** clear prior adjustments — it relies on Sylius' own `ORDER_UNIT_PROMOTION_ADJUSTMENT` lifecycle to remove them.

Wiring:

- `SetonoSyliusTierPricingPlugin` extends `AbstractResourceBundle` + `SyliusPluginTrait`, ORM-only. It overrides **both** `getPath()` (returns `dirname(__DIR__)` so Sylius sees the repo root) **and** `getConfigFilesPath()` (returns `<root>/config/doctrine/<format>` — without this override, Sylius' `AbstractResourceBundle::getConfigFilesPath()` would still look under `Resources/config/doctrine/`).
- DI services live in `config/services/*.xml`, imported from `config/services.xml`. The extension (`SetonoSyliusTierPricingExtension`) implements `PrependExtensionInterface`: its `prepend()` loads `config/sylius_twig_hooks.yaml` into `sylius_twig_hooks` so consumers don't have to import a YAML file.
- `Form\Extension\ProductTypeExtension` adds the `priceTiers` field to the Sylius `ProductType`. `Form\Type\PriceTierCollectionType` extends `Symfony\UX\LiveComponent\Form\Type\LiveCollectionType` (mirroring how Sylius admin handles product images) so add/delete fire server-side via Symfony UX Live Components — no client-side prototype-cloning JS. `Form\Type\PriceTierType` provides `empty_data` defaults on `quantity` (`'1'`) and `discount` (`'0.0'`); these are required because LiveCollectionType re-binds the form on `addCollectionItem` *before* the user types anything, and the model's `setQuantity(int)` is non-nullable.
- The admin product tab is rendered by Twig hooks (`templates/admin/product/form/{side_navigation,sections}/price_tiers.html.twig`) registered against `sylius_admin.product.{update,create}.content.form.{side_navigation,sections}` — the canonical Sylius 2 hook points (declared by the core in `vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/Resources/config/app/twig_hooks/product/update.yaml`). `ProductFormMenuSubscriber` is gone in v2 — Sylius 2 has no `ProductMenuBuilderEvent`.

## Conventions

- All `src/` files use `declare(strict_types=1);` and the sylius-labs ECS ruleset — run `composer fix-style` before committing.
- Discounts are kept as numeric strings and computed with `brick/math` `BigDecimal` to avoid float drift; do not switch to `float` arithmetic.
- The default branch on GitHub is `1.x`. Active development for the v2 line lands on `2.x`. **PRs targeting v2 should set `--base 2.x`.** PRs targeting v1 maintenance still use `1.x`.
- CI matrix exercises PHP 8.2/8.3/8.4 × Symfony 6.4/7.4 × lowest/highest deps via the `setono/sylius-plugin/<job>@v2` composite actions. Keep changes compatible across that matrix.
- For Doctrine: ORM 3 is in play (Sylius 2 pulls it). Use `#[ORM\*]` attributes — not `@ORM\*` PHPDoc tags, which are silently ignored in attribute-mapped entities.
- For form fields with non-nullable scalar setters that participate in a `LiveCollectionType`, always set `empty_data`.
- Do **not** revert `getConfigFilesPath()` to default — Doctrine mapping discovery breaks immediately.
