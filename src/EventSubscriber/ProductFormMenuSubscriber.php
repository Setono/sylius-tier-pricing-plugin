<?php

declare(strict_types=1);

namespace Setono\SyliusTierPricingPlugin\EventSubscriber;

use Sylius\Bundle\AdminBundle\Event\ProductMenuBuilderEvent;
use Sylius\Bundle\AdminBundle\Menu\ProductFormMenuBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProductFormMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductMenuBuilderEvent::class => 'addPriceTiersTab',
            ProductFormMenuBuilder::EVENT_NAME => 'addPriceTiersTab',
        ];
    }

    public function addPriceTiersTab(ProductMenuBuilderEvent $event): void
    {
        $event->getMenu()
            ->addChild('price_tiers')
            ->setAttribute('template', '@SetonoSyliusTierPricingPlugin/admin/product/tab/_price_tiers.html.twig')
            ->setLabel('setono_sylius_tier_pricing.ui.price_tiers')
        ;
    }
}
