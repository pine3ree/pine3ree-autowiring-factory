<?php

/**
 * @package p3im-abstract-factories
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Container;

use Psr\Container\ContainerInterface;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;

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
            = $container->has(ParamsResolverInterface::class)
            ? $container->get(ParamsResolverInterface::class)
            : new ParamsResolver($container);

        $args = $paramsResolver->resolve([$fqcn, '__construct']);

        return empty($args) ? new $fqcn() : new $fqcn(...$args);
    }
}
