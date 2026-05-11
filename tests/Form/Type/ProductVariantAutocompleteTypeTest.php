<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Tests\Form\Type;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusTierPricingPlugin\Form\Type\ProductVariantAutocompleteType;
use Sylius\Bundle\AdminBundle\Form\Type\TranslatableAutocompleteType;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductVariant;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

final class ProductVariantAutocompleteTypeTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_exposes_a_predictable_block_prefix_for_template_overrides(): void
    {
        self::assertSame(
            'setono_sylius_tier_pricing_product_variant_autocomplete',
            (new ProductVariantAutocompleteType(ProductVariant::class))->getBlockPrefix(),
        );
    }

    #[Test]
    public function it_parents_to_translatable_autocomplete_so_variant_translations_are_searched(): void
    {
        self::assertSame(
            TranslatableAutocompleteType::class,
            (new ProductVariantAutocompleteType(ProductVariant::class))->getParent(),
        );
    }

    #[Test]
    public function it_advertises_its_alias_and_route_via_the_as_entity_autocomplete_field_attribute(): void
    {
        // Without the AsEntityAutocompleteField attribute Symfony UX has no way to map our type back to an
        // alias when handling the autocomplete request — silently breaking the dropdown. Lock it down.
        $attribute = AsEntityAutocompleteField::getInstance(ProductVariantAutocompleteType::class);

        self::assertNotNull($attribute);
        self::assertSame('setono_sylius_tier_pricing_product_variant', $attribute->getAlias());
        self::assertSame('sylius_admin_entity_autocomplete', $attribute->getRoute());
    }

    #[Test]
    public function it_defaults_the_class_option_to_the_injected_product_variant_class(): void
    {
        self::assertSame(ProductVariant::class, $this->resolveOptions()['class']);
    }

    #[Test]
    public function filter_query_default_is_null_when_extra_options_is_empty(): void
    {
        self::assertNull($this->resolveOptions(['extra_options' => []])['filter_query']);
    }

    #[Test]
    public function filter_query_default_is_null_when_product_id_is_missing_from_extra_options(): void
    {
        self::assertNull($this->resolveOptions(['extra_options' => ['unrelated_key' => 'foo']])['filter_query']);
    }

    #[Test]
    public function filter_query_returns_a_callable_when_extra_options_supplies_a_product_id(): void
    {
        self::assertIsCallable($this->resolveOptions(['extra_options' => ['product_id' => 42]])['filter_query']);
    }

    #[Test]
    public function the_resolved_filter_query_scopes_the_query_builder_to_the_supplied_product_id(): void
    {
        $filterQuery = $this->resolveOptions(['extra_options' => ['product_id' => 42]])['filter_query'];
        self::assertIsCallable($filterQuery);

        // Doctrine's QueryBuilder is fluent (`andWhere()->setParameter()`), so the prophecy must keep
        // returning the reveal across calls — otherwise the second chained call lands on something
        // that isn't a QueryBuilder and the closure throws.
        $qb = $this->prophesize(QueryBuilder::class);
        $qbReveal = $qb->reveal();

        $qb->andWhere('entity.product = :setono_tier_pricing_product')
            ->shouldBeCalled()
            ->willReturn($qbReveal)
        ;
        $qb->setParameter('setono_tier_pricing_product', 42)
            ->shouldBeCalled()
            ->willReturn($qbReveal)
        ;

        $repository = $this->prophesize(EntityRepository::class)->reveal();

        $filterQuery($qbReveal, 'any-search-query', $repository);
    }

    /**
     * Drive the type's `configureOptions()` against a bare OptionsResolver. We pre-define `extra_options`
     * here because in production it's `Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension` (a
     * type extension on ChoiceType) that defines it — and a unit-level OptionsResolver doesn't get
     * extensions wired in. Same shortcut sidesteps the rest of the EntityType chain.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(array $overrides = []): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('extra_options', []);

        (new ProductVariantAutocompleteType(ProductVariant::class))->configureOptions($resolver);

        /** @var array<string, mixed> $resolved */
        $resolved = $resolver->resolve($overrides);

        return $resolved;
    }
}
