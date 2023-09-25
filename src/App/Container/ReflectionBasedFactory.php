<?php

/**
 * @author pine3ree https://github.com/pine3ree
 * @package p3im
 * @subpackage p3im-app
 */

namespace App\Container;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Psr\Container\ContainerInterface;

use function sprintf;

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
        $rc = $this->getReflectionClass($fqcn);
        $params = $this->constructorParams[$fqcn];

        // Parameter-less object constructor or consructor-less object
        if (empty($params)) {
            return new $fqcn();
        }

        $args = [];
        foreach ($params as $param) {
            $prc = $param->getClass();
            if ($prc instanceof ReflectionClass) {
                $dep_fqcn = $prc->getName();
                $args[] = $dep_fqcn === ContainerInterface::class
                    ? $container
                    : $container->get($dep_fqcn);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new RuntimeException(sprintf(
                    "Cannot build the object of class `{$fqcn}`, parameter `%s`"
                    . " is not a dependency FQCN!",
                    $param->getName()
                ));
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
