<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\Form\Type;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\AdminBundle\Form\Type\TranslatableAutocompleteType;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

#[AsEntityAutocompleteField(
    alias: 'setono_sylius_tier_pricing_product_variant',
    route: 'sylius_admin_entity_autocomplete',
)]
final class ProductVariantAutocompleteType extends AbstractType
{
    public function __construct(
        private readonly string $productVariantClass,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('class', $this->productVariantClass);

        // Scope the autocomplete results to a single product when extra_options.product_id is provided.
        // The closure-default resolves at autocomplete-request time after extra_options has been forwarded
        // by Symfony UX's controller, so the filter sees the parent product's id without any per-request wiring.
        $resolver->setDefault('filter_query', static function (Options $options): ?callable {
            $extraOptions = $options['extra_options'];
            if (!is_array($extraOptions)) {
                return null;
            }

            $productId = $extraOptions['product_id'] ?? null;
            if (null === $productId) {
                return null;
            }

            return static function (QueryBuilder $qb, string $query, EntityRepository $repository) use ($productId): void {
                $qb
                    ->andWhere(TranslatableAutocompleteType::ENTITY_ALIAS . '.product = :setono_tier_pricing_product')
                    ->setParameter('setono_tier_pricing_product', $productId)
                ;
            };
        });
    }

    public function getParent(): string
    {
        return TranslatableAutocompleteType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_tier_pricing_product_variant_autocomplete';
    }
}
