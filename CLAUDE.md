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

Test-app console — prefer running from the project root via `./tests/Application/bin/console <cmd>` (rather than `cd`ing into `tests/Application` first). The test app is its own Symfony app (own `composer.json`, own `Kernel.php`), but its CLI works fine from the repo root.

- `./tests/Application/bin/console lint:container` / `lint:yaml config` / `lint:twig templates`
- `./tests/Application/bin/console doctrine:database:create --if-not-exists` then `doctrine:schema:create` to spin up the integration DB. **Functional tests require MySQL/MariaDB** at the URL declared in `tests/Application/.env`; SQLite won't work — the schema uses MySQL-specific column types and collations.

Booting the test app locally (full UI smoke test):

```bash
./tests/Application/bin/console doctrine:database:create --if-not-exists
./tests/Application/bin/console doctrine:schema:create
./tests/Application/bin/console sylius:fixtures:load default --no-interaction  # admin login: sylius / sylius
./tests/Application/bin/console lexik:jwt:generate-keypair --skip-if-exists
./tests/Application/bin/console assets:install tests/Application/public --symlink
(cd tests/Application && yarn install && yarn build)                            # builds public/build/{admin,shop}/
(cd tests/Application && symfony server:status) || (cd tests/Application && symfony server:start -d)  # https://127.0.0.1:8000/admin
```

Notes:
- Always run `symfony server:status` first — `server:start` fails noisily if a server is already up. `server:status` is **per-project** (it inspects cwd), so `cd tests/Application` before checking.
- When you build or change a feature with a UI surface (admin form, grid, page), verify it via the Playwright MCP after booting the test app — don't rely on PHPUnit/PHPStan/ECS alone. Twig hook misconfiguration only shows up in the rendered DOM.

The repo root `./init` script is a one-shot rename tool inherited from the Setono plugin skeleton; do not run it on this repo. (It was deleted in the v1.x branch — re-check before touching.)

## Architecture

Three collaborating pieces drive tier pricing. Reading them together is the fastest way to understand the plugin:

1. **`Model\PriceTier`** — Doctrine entity owning `quantity`, `discount` (stored as string for `brick/math` precision), and optional `product` / `productVariant` / `channel` scoping. `setProductVariant()` always syncs `product` from the variant; `setProduct()` clears a variant that doesn't belong to the new product (invariant: a tier's variant must be one of its product's variants). The `Model\ProductTrait` adds the `priceTiers` collection to the Sylius `Product` via a `#[ORM\OneToMany]` attribute (Doctrine ORM 3 dropped PHPDoc annotations) and is mixed into the test app's `App\Entity\Product` (`tests/Application/Entity/Product.php`).

2. **`Provider\PriceTierProvider`** — given a quantity + variant + channel, returns the single best-matching tier. It groups all of the product's tiers by quantity, then for each quantity picks the most-specific tier using this precedence: `(channel + variant) > variant > channel > generic`. The result is sorted ascending by quantity (`ksort`, see `src/Provider/PriceTierProvider.php:67` — added because tiers can come out of the DB unordered), and `getPriceTier()` walks that list to find the highest quantity threshold the requested quantity meets. Channel defaults to `ChannelContextInterface` when not passed.

3. **`OrderProcessor\PriceTiersOrderProcessor`** — tagged `sylius.order_processor` with **priority 15** (runs *before* tax/shipping; `config/services/order_processor.php`). For each order item it asks the provider for a tier, computes `total * discount%` rounded with `RoundingMode::CEILING`, splits the integer discount across units via Sylius' `sylius.distributor.integer` (renamed in v2 from `sylius.integer_distributor`), and attaches one `ORDER_UNIT_PROMOTION_ADJUSTMENT` per unit with `originCode = 'tier_pricing'` and metadata (`priceTierId`, `priceTierQuantity`, `priceTierDiscount`). It deliberately does **not** clear prior adjustments — it relies on Sylius' own `ORDER_UNIT_PROMOTION_ADJUSTMENT` lifecycle to remove them.

Wiring:

- `SetonoSyliusTierPricingPlugin` extends `AbstractResourceBundle` + `SyliusPluginTrait`, ORM-only. It overrides **both** `getPath()` (returns `dirname(__DIR__)` so Sylius sees the repo root) **and** `getConfigFilesPath()` (returns `<root>/config/doctrine/<format>` — without this override, Sylius' `AbstractResourceBundle::getConfigFilesPath()` would still look under `Resources/config/doctrine/`).
- DI services live in `config/services/*.php` (PHP DSL via `ContainerConfigurator`), imported from `config/services.php`, loaded by the extension via `PhpFileLoader`. **Do not** ship XML service config — the Setono Sylius-2 convention is PHP for IDE refactoring, PHPStan analysis at compile time, and FQCN service ids. Each leaf file starts with `namespace Symfony\Component\DependencyInjection\Loader\Configurator;` so `service()`, `param()`, etc. resolve as bare function calls. The extension implements `PrependExtensionInterface`: its `prepend()` injects the plugin's `sylius_twig_hooks` configuration directly via `prependExtensionConfig()` so consumers don't need to import anything. **Convention:** other-bundle configuration belongs in `prepend()` and **must be inlined as a PHP array** — do not read/parse a YAML file from disk and forward it.
- `Form\Extension\ProductTypeExtension` adds the `priceTiers` field to the Sylius `ProductType` and forwards the parent product down as `entry_options.product` so each per-row `PriceTierType` (including freshly-added rows that aren't yet linked to a product) can render the variant selector for the current product. `Form\Type\PriceTierCollectionType` extends `Symfony\UX\LiveComponent\Form\Type\LiveCollectionType` (mirroring how Sylius admin handles product images) so add/delete fire server-side via Symfony UX Live Components — no client-side prototype-cloning JS. It uses an `entry_options` normalizer to always merge `label => false` with whatever the caller passes (so the product passed down by `ProductTypeExtension` doesn't wipe the label default). To survive LiveCollectionType's empty-bind cycle on `addCollectionItem`, `PriceTier::setQuantity()` and `setDiscount()` are nullable-and-no-op-on-null rather than using form-level `empty_data` defaults — keeps the workaround out of `PriceTierType` and makes the model the single source of truth for its defaults (`quantity = 1`, `discount = '0.0'`).
- The admin product tab is rendered by Twig hooks (`templates/admin/product/form/{side_navigation,sections}/price_tiers.html.twig`) registered against `sylius_admin.product.{update,create}.content.form.{side_navigation,sections}` — the canonical Sylius 2 hook points (declared by the core in `vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/Resources/config/app/twig_hooks/product/update.yaml`). `ProductFormMenuSubscriber` is gone in v2 — Sylius 2 has no `ProductMenuBuilderEvent`.

## Conventions

- All `src/` files use `declare(strict_types=1);` and the sylius-labs ECS ruleset — run `composer fix-style` before committing.
- Discounts are kept as numeric strings and computed with `brick/math` `BigDecimal` to avoid float drift; do not switch to `float` arithmetic.
- The default branch on GitHub is `1.x`. Active development for the v2 line lands on `2.x`. **PRs targeting v2 should set `--base 2.x`.** PRs targeting v1 maintenance still use `1.x`.
- CI matrix exercises PHP 8.2/8.3/8.4 × Symfony 6.4/7.4 × lowest/highest deps via the `setono/sylius-plugin/<job>@v2` composite actions. Keep changes compatible across that matrix.
- For Doctrine: ORM 3 is in play (Sylius 2 pulls it). Use `#[ORM\*]` attributes — not `@ORM\*` PHPDoc tags, which are silently ignored in attribute-mapped entities.
- Required scalar setters on entities that participate in a `LiveCollectionType` should accept null and treat it as a no-op (keeping the existing value), instead of forcing `empty_data` defaults at the form level. LiveCollectionType re-binds the parent form on `addCollectionItem` before the user types anything; non-nullable setters explode with `InvalidTypeException`, and pushing the default to the form means the model has two sources of truth for its initial state.
- Per-entry form fields inside a `LiveCollectionType` that depend on **parent-entity context** (e.g. the variant selector needing to know which product owns the tier) must receive that context via `entry_options` from the outer form extension, not via a `PRE_SET_DATA` listener on the entry type that reads `$entry->getParent()`. The PRE_SET_DATA approach fires for entities loaded from the DB but silently omits the field on freshly-added rows because Live Components build the empty entry's form *before* the parent's `add*()` assigns the backreference.
- Do **not** revert `getConfigFilesPath()` to default — Doctrine mapping discovery breaks immediately.
- Configuration of other bundles lives in `SetonoSyliusTierPricingExtension::prepend()` as inlined PHP arrays passed to `prependExtensionConfig()`. Do not read YAML files from disk inside `prepend()` and forward the parsed result.
- DI service configuration is **PHP DSL**, not XML. New services go under `config/services/<topic>.php` using `ContainerConfigurator`. Use the FQCN as the service id and alias `*Interface` to it for forward compatibility.
- **Mocks: always use Prophecy** (`Prophecy\PhpUnit\ProphecyTrait` + `$this->prophesize(...)->reveal()`). Don't mix with PHPUnit's native `createMock()` / `createStub()` — keep doubles consistent across the suite. The `setono/sylius-plugin` toolchain ships `jangregor/phpstan-prophecy` so PHPStan understands `prophesize()` return types.
- **Form-type tests**: extend `Symfony\Component\Form\Test\TypeTestCase` (see <https://symfony.com/doc/6.4/form/unit_testing.html>). Register the SUT + its child types via `getTypes()` (cleaner than `PreloadedExtension` when types are easily instantiable); for Sylius types that need services, prophesize the deps and pass them to the type's constructor. Use `$this->factory->create(SUT::class, $initialData)` then `submit([...])`, and assert against `isSynchronized()` + `getData()` + child-form presence with `$form->has('fieldName')`. **For form types whose parent is `LiveCollectionType`**, mirror the upstream `symfony/ux-live-component` test (`Symfony\UX\LiveComponent\Tests\Unit\Form\Type\LiveCollectionTypeTest`): call `createView()` and assert that `$view->vars['button_add']->vars['block_prefixes']` contains `live_collection_button_add` (and `button_delete` on each entry contains `live_collection_button_delete`). That's the only test that proves the Live decoration actually ran — `configureOptions()` assertions alone won't catch a regression that flips `getParent()` back to plain `CollectionType`.
- Before each commit, run `composer fix-style`, `composer analyse`, and `composer phpunit` and fix what they flag. Don't commit on top of pre-existing failures.
- Prefer **relative paths** in shell commands (`./tests/Application/bin/console ...`, `composer ...`). Absolute paths inside the working directory trigger Claude Code permission prompts. If you `cd` into `tests/Application/`, `cd` back to the project root before subsequent commands rather than chaining absolute paths.
