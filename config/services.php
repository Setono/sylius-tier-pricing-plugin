<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->import('services/form.php');
    $container->import('services/order_processor.php');
    $container->import('services/provider.php');
};
