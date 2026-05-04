# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Sylius plugin (`setono/sylius-tier-pricing-plugin`) that adds quantity-based price tiers to Sylius. PHP 8.1+, Symfony 5.4/6.4, Sylius ~1.12.13. Source lives in `src/`, namespace `Setono\SyliusTierPricingPlugin\`. A full Sylius test app is embedded in `tests/Application/` and used as the kernel for PHPUnit + DI tests.

## Commands

Composer scripts (also available as zsh aliases from `~/CLAUDE.md`: `cf`/`ca`/`cfca`):

- `composer phpunit` — run the test suite (`composer phpunit -- --filter PriceTierProviderTest` for a single test).
- `composer analyse` — Psalm static analysis (config in `psalm.xml`).
- `composer check-style` / `composer fix-style` — ECS using sylius-labs ruleset (`ecs.php`).
- `vendor/bin/rector process --dry-run` — Rector check (kept advisory in CI, see `.github/workflows/build.yaml`).
- `vendor/bin/infection` — mutation testing (`infection.json.dist`).

Test-app console (must `cd tests/Application` first — it's its own Symfony app with its own `composer.json`):

- `bin/console lint:container` / `lint:yaml ../../src/Resources` / `lint:twig ../../src/Resources`
- `bin/console doctrine:database:create` then `doctrine:schema:create` to spin up the integration DB. CI uses `DATABASE_URL=mysql://root:root@127.0.0.1/sylius?serverVersion=8.0`.

The repo root `./init` script is a one-shot rename tool inherited from the Setono plugin skeleton; do not run it on this repo.

## Architecture

Three collaborating pieces drive tier pricing. Reading them together is the fastest way to understand the plugin:

1. **`Model\PriceTier`** — Doctrine entity owning `quantity`, `discount` (stored as string for `brick/math` precision), and optional `product` / `productVariant` / `channel` scoping. `setProductVariant()` always syncs `product` from the variant; `setProduct()` clears a variant that doesn't belong to the new product (invariant: a tier's variant must be one of its product's variants). The `Model\ProductTrait` adds the `priceTiers` collection to the Sylius `Product` and is mixed in via the test app's `App\Entity\Product` (see `tests/Application/Model/`).

2. **`Provider\PriceTierProvider`** — given a quantity + variant + channel, returns the single best-matching tier. It groups all of the product's tiers by quantity, then for each quantity picks the most-specific tier using this precedence: `(channel + variant) > variant > channel > generic`. The result is sorted ascending by quantity (`ksort`, see file:line `src/Provider/PriceTierProvider.php:68` — added because tiers can come out of the DB unordered), and `getPriceTier()` walks that list to find the highest quantity threshold the requested quantity meets. Channel defaults to `ChannelContextInterface` when not passed.

3. **`OrderProcessor\PriceTiersOrderProcessor`** — tagged `sylius.order_processor` with **priority 15** (runs *before* tax/shipping; `services/order_processor.xml`). For each order item it asks the provider for a tier, computes `total * discount%` rounded with `RoundingMode::CEILING`, splits the integer discount across units via Sylius' `IntegerDistributorInterface`, and attaches one `ORDER_UNIT_PROMOTION_ADJUSTMENT` per unit with `originCode = 'tier_pricing'` and metadata (`priceTierId`, `priceTierQuantity`, `priceTierDiscount`). It deliberately does **not** clear prior adjustments — it relies on Sylius' own `ORDER_UNIT_PROMOTION_ADJUSTMENT` lifecycle to remove them (see comment in `PriceTiersOrderProcessor::process`).

Wiring:

- `SetonoSyliusTierPricingPlugin` extends `AbstractResourceBundle` + `SyliusPluginTrait`, ORM-only.
- DI services live in `src/Resources/config/services/*.xml`, imported from `services.xml`. Resource config (Sylius resource registration), Doctrine mapping, validation, routes, and translations are all under `src/Resources/`.
- `Form\Extension\ProductTypeExtension` + `Form\Type\PriceTierCollectionType` / `PriceTierType` add the price-tier UI to the admin product form. `EventSubscriber\ProductFormMenuSubscriber` injects the menu tab.

## Conventions

- All `src/` files use `declare(strict_types=1);` and the sylius-labs ECS ruleset — run `composer fix-style` before committing.
- Discounts are kept as numeric strings and computed with `brick/math` `BigDecimal` to avoid float drift; do not switch to `float` arithmetic.
- The default branch for PRs is `master`. Active development branch is `2.x`.
- CI matrix exercises PHP 8.1/8.2 × Symfony 5.4/6.4 × lowest/highest deps — keep changes compatible across that matrix (no PHP 8.3+ syntax, no Symfony 7-only APIs).
