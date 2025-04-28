<?php

/**
 * @package p3im-abstract-factories
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Container;

use Psr\Container\ContainerInterface;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use RuntimeException;
use SplObjectStorage;
use Throwable;

use function method_exists;

/**
 * A generic factory that resolves and injects dependencies using reflection
 */
class ReflectionBasedFactory
{
    private SplObjectStorage $storage;

    public function __construct()
    {
        $this->storage = new SplObjectStorage();
    }

    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        if (!method_exists($fqcn, '__construct')) {
            try {
                return new $fqcn();
            } catch (Throwable $ex) {
                throw new RuntimeException(
                    "Unable ti instantiate a constructor-less object of class `{$fqcn}`"
                );
            }
        }

        $storage = $this->storage;
        if ($storage->offsetExists($container)) {
            $paramsResolver = $storage->offsetGet($container);
        } else {
            if ($container->has(ParamsResolverInterface::class)) {
                $paramsResolver = $container->get(ParamsResolverInterface::class);
            } else {
                $paramsResolver = new ParamsResolver($container);
            }
            $storage->attach($container, $paramsResolver);
        }

        $args = $paramsResolver->resolve([$fqcn, '__construct']);

        return empty($args) ? new $fqcn() : new $fqcn(...$args);
    }
}
