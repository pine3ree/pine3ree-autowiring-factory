<?php

/**
 * @author pine3ree https://github.com/pine3ree
 * @package p3im
 * @subpackage p3im-action
 */

namespace App\Container;

use ReflectionClass;
use RuntimeException;
use Psr\Container\ContainerInterface;

use function sprintf;

/**
 * A generic factory that resolves and injects dependencies using reflection
 */
class ReflectionBasedFactory
{
    /** @var \ReflectionClass[] */
    private $reflectionClasses = [];

    /** @var \ReflectionParameter[] */
    private $constructorParams = [];

    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        $rc = $this->getReflectionClass($fqcn);

        $params = $this->constructorParams[$fqcn];

        // parameter-less object constructor
        if (empty($params)) {
            return new $fqcn();
        }

        $args = [];
        foreach ($params as $param) {
            $prc = $param->getClass();
            if ($prc instanceof ReflectionClass) {
                $depFQCN = $prc->getName();
                $args[] = ContainerInterface::class === $depFQCN
                    ? $container
                    : $container->get($depFQCN);
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
     * @return ReflectionParameter[]
     */
    private function getReflectionClass(string $fqcn): ReflectionClass
    {
        if (isset($this->reflectionClasses[$fqcn])) {
            return $this->reflectionClasses[$fqcn];
        }

        $rc = new ReflectionClass($fqcn);

        // constructor-less dependencies
        if (is_null($constructor = $rc->getConstructor())) {
            $this->constructorParams[$fqcn] = [];
        }

        /** @var \ReflectionParameter[] $params */
        $this->constructorParams[$fqcn] = $constructor->getParameters();

        return $rc;
    }
}
