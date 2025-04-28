<?php

/**
 * @package pine3ree-abstract-factories
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Container\Factory;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplObjectStorage;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;

use function class_exists;
use function method_exists;

/**
 * A generic factory that resolves and injects dependencies using reflection
 */
class ReflectionBasedFactory
{
    /**
     * Params resolvers cached by container
     * @var SplObjectStorage<ContainerInterface, ParamsResolverInterface>
     */
    private SplObjectStorage $storage;

    public function __construct()
    {
        $this->storage = new SplObjectStorage();
    }

    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        if (!class_exists($fqcn)) {
            throw new RuntimeException(
                "Unable to load the requested class `{$fqcn}`"
            );
        }

        if (!method_exists($fqcn, '__construct')) {
            return new $fqcn();
        }

        $rc = new ReflectionClass($fqcn);
        $rm = $rc->getConstructor();
        /** @var ReflectionMethod $rm Existence tested before */
        if ($rm->isPrivate()) {
            throw new RuntimeException(
                "Unable to call the private constructor of the requested class `{$fqcn}`"
            );
        }

        $storage = $this->storage;
        if ($storage->offsetExists($container)) {
            $paramsResolver = $storage->offsetGet($container);
        } else {
            if ($container->has(ParamsResolverInterface::class)) {
                $paramsResolver = $container->get(ParamsResolverInterface::class);
                if (! $paramsResolver instanceof ParamsResolverInterface) {
                    $paramsResolver = new ParamsResolver($container);
                }
            } else {
                $paramsResolver = new ParamsResolver($container);
            }
            $storage->attach($container, $paramsResolver);
        }

        $args = $paramsResolver->resolve([$fqcn, '__construct']);

        return empty($args) ? new $fqcn() : new $fqcn(...$args);
    }

    /**
     * Fetch a catched resolver for given container, if any
     *
     * @param ContainerInterface $container
     * @return ParamsResolverInterface|null
     *
     * @internal Used in unit tests
     */
    public function getCachedParamsResolver(ContainerInterface $container): ?ParamsResolverInterface
    {
        if ($this->storage->offsetExists($container)) {
            return $this->storage->offsetGet($container);
        }

        return null;
    }
}
