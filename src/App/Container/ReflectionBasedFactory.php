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
    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        $rc = new ReflectionClass($fqcn);
        $ctor = $rc->getConstructor();

        // constructor-less services
        if (empty($ctor)) {
            return new $fqcn();
        }

        /** @var \ReflectionParameter[] $params */
        $params = $ctor->getParameters();

        // parameter-less services
        if (empty($params)) {
            return new $fqcn();
        }

        $ctor_args = [];
        foreach ($params as $param) {
            $prc = $param->getClass();
            if (! $prc instanceof ReflectionClass) {
                if ($param->isDefaultValueAvailable()) {
                    $ctor_args[] = $param->getDefaultValue();
                    continue;
                }
                throw new RuntimeException(sprintf(
                    "Cannot build a constructor, parameter `%s` is not a dependency FQCN!",
                    $param->getName()
                ));
            }
            $ctor_args[] = ContainerInterface::class === ($depFQCN = $prc->getName())
                ? $container
                : $container->get($depFQCN);
        }

        return $rc->newInstanceArgs($ctor_args);
    }
}
