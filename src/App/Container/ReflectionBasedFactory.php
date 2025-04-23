<?php

/**
 * @package    p3im
 * @subpackage p3im-app-container
 * @author     pine3ree https://github.com/pine3ree
 */

namespace App\Container;

use App\Container\ParamsResolver;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Throwable;

use function class_exists;
use function get_class;
use function interface_exists;
//use function sprintf;

/**
 * A generic factory that resolves and injects dependencies using reflection
 */
class ReflectionBasedFactory
{
    /** @var ReflectionClass[]|array<string, ReflectionClass> */
    private $reflectionClasses = [];

    /** @var ReflectionParameter[]|array<string, ReflectionParameter> */
    private $constructorParams = [];

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

        $rc = $this->getReflectionClass($fqcn);
        $__r_params = $this->constructorParams[$fqcn];

        // Parameter-less object constructor or consructor-less object
        if (empty($__r_params)) {
            return new $fqcn();
        }

        $args = [];
        foreach ($__r_params as $rp) {
            $rp_name = $rp->getName();
            $rp_type = $rp->getType();
            if ($rp_type instanceof ReflectionNamedType && !$rp_type->isBuiltin()) {
                $rp_fqcn = $rp_type->getName();
                if (interface_exists($rp_fqcn, true) || class_exists($rp_fqcn, true)) {
                    if ($rp_fqcn === ContainerInterface::class || $rp_fqcn === get_class($container)) {
                        $args[] = $container;
                    } elseif ($container->has($rp_fqcn)) {
                        $args[] = $container->get($rp_fqcn);
                    } else {
                        // Try instantating with argument-less constructor call
                        try {
                            $args[] = new $rp_fqcn();
                        } catch (Throwable $ex) {
                            throw new RuntimeException(
                                "Unable to create an instance of `{$rp_fqcn}`"
                                . " for the argument with name `{$rp_name}`!",
                            );
                        }
                    }
                } else {
                    throw new RuntimeException(
                        "`{$rp_fqcn}` is neither a valid interface nor a class name"
                        . " for given dependency named `{$rp_name}`!",
                    );
                }
            } elseif ($container->has($rp_name)) {
                $args[] = $container->get($rp_name);
            } elseif ($rp->isDefaultValueAvailable()) {
                $args[] = $rp->getDefaultValue();
            } else {
                throw new RuntimeException(
                    "Cannot build the object of class `{$fqcn}`:"
                    . " argument `{$rp_name}` cannot be resolved!"
                );
            }
        }

        return $rc->newInstanceArgs($args);
    }

    /**
     * Resolve and cache a reflection-class and its constructor-parameters for a give FQCNù
     *
     * @param string $fqcn
     * @return ReflectionClass
     */
    private function getReflectionClass(string $fqcn): ReflectionClass
    {
        $rc = $this->reflectionClasses[$fqcn] ?? null;
        if (!empty($rc)) {
            return $rc;
        }

        $rc = new ReflectionClass($fqcn);
        $constructor = $rc->getConstructor();

        $this->reflectionClasses[$fqcn] = $rc;
        $this->constructorParams[$fqcn] = $constructor instanceof ReflectionMethod
            ? $constructor->getParameters()
            : []; // Constructor-less service

        return $rc;
    }
}
