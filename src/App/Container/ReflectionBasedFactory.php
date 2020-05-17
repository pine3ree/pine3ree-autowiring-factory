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
    private $resolvedParams = [];

    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        /** @var \ReflectionParameter[] $params */
        $params = $this->getConstructorParams($fqcn);

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
     * @param string $fqcn
     * @return ReflectionParameter[]
     */
    private function getConstructorParams(string $fqcn): array
    {
        if (isset($this->resolvedParams[$fqcn])) {
            return $this->resolvedParams[$fqcn];
        }

        $rc = new ReflectionClass($fqcn);

        // constructor-less dependencies
        if (is_null($ctor = $rc->getConstructor())) {
            $this->resolvedParams[$fqcn] = [];
            return [];
        }

        /** @var \ReflectionParameter[] $params */
        $this->resolvedParams[$fqcn] = $params = $ctor->getParameters();
        return $params;
    }
}
