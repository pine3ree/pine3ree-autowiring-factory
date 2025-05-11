<?php

/**
 * @package pine3ree-autowiring-factory
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Container\Factory;

use Psr\Container\ContainerInterface;
use ReflectionMethod;
use RuntimeException;
use SplObjectStorage;
use Throwable;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Helper\Reflection;

use function class_exists;
use function method_exists;

/**
 * A generic factory that resolves and injects dependencies using reflection
 */
class AutowiringFactory
{
    /**
     * Params resolvers cached by container
     * @var SplObjectStorage<ContainerInterface, ParamsResolverInterface>|null
     */
    private ?SplObjectStorage $cache = null;

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

        $rm = Reflection::getConstructor($fqcn);
        /** @var ReflectionMethod $rm Existence tested before */
        if ($rm->isPrivate()) {
            throw new RuntimeException(
                "Unable to call the private constructor of the requested class `{$fqcn}`"
            );
        }

        $cache = $this->cache ?? $this->cache = new SplObjectStorage();

        if ($cache->contains($container)) {
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
}
