<?php

/**
 * @package    p3im
 * @subpackage p3im-app-container
 * @author     pine3ree https://github.com/pine3ree
 */

namespace App\Container;

use App\Container\ParamsResolver;
use Psr\Container\ContainerInterface;

/**
 * A generic factory that resolves and injects dependencies using reflection
 */
class ReflectionBasedFactory
{
    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        $has_constructor = method_exists($fqcn, '__construct');

        if (!$has_constructor) {
            return new $fqcn();
        }

        $paramsResolver
            = $container->has(ParamsResolver::class)
            ? $container->get(ParamsResolver::class)
            : new ParamsResolver($container);

        $args = $paramsResolver->resolve([$fqcn, '__construct']);

        return empty($args) ? new $fqcn() : new $fqcn(...$args);
    }
}
