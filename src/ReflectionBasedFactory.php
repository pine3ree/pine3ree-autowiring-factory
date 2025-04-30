<?php

/**
 * @package pine3ree-generic-factories
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Container\Factory;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplObjectStorage;
use Throwable;
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
    private SplObjectStorage $cache;

    public function __construct()
    {
        $this->cache = new SplObjectStorage();
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

        $cache = $this->cache;
        if ($cache->offsetExists($container)) {
            $paramsResolver = $cache->offsetGet($container);
        } else {
            if ($container->has(ParamsResolverInterface::class)) {
                $paramsResolver = $container->get(ParamsResolverInterface::class);
                if (! $paramsResolver instanceof ParamsResolverInterface) {
                    $paramsResolver = new ParamsResolver($container);
                }
            } else {
                $paramsResolver = new ParamsResolver($container);
            }
            $cache->attach($container, $paramsResolver);
        }

        try {
            $args = $paramsResolver->resolve([$fqcn, '__construct']);
            return empty($args) ? new $fqcn() : new $fqcn(...$args);
        } catch (Throwable $ex) {
            throw new RuntimeException($ex->getMessage());
        }
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
        if ($this->cache->offsetExists($container)) {
            return $this->cache->offsetGet($container);
        }

        return null;
    }
}
