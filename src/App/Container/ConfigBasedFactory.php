<?php

/**
 * @package p3im-im
 * @subpackage p3im-app
 * @author pine3ree https://github.com/pine3ree
 */

namespace App\Container;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

use function class_exists;

/**
 * A generic factory that resolves and injects dependencies from configuration
 */
class ConfigBasedFactory
{
    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        $config = $container->has('config') ? $container->get('config') : null;
        $config = $config['dependencies'][static::class][$fqcn] ?? null;

        if (empty($config)) {
            try {
                return new $fqcn();
            } catch (Throwable $ex) {
                throw new RuntimeException(
                    "Mandatory factory configuration not found for class `{$fqcn}`!"
                );
            }
        }

        $dependencies = [];
        foreach ($config as $dep_fqcn) {
            if ($dep_fqcn === ContainerInterface::class) {
                $dependencies[] = $container;
                continue;
            }
            if (!class_exists($dep_fqcn)) {
                throw new RuntimeException(
                    "Unable to load the dependency class `{$dep_fqcn}`!"
                );
            }
            if (!$container->has($dep_fqcn)) {
                throw new RuntimeException(
                    "Unable to load the dependency `{$dep_fqcn}` for class `{$fqcn}`!"
                );
            }
            $dependencies[] = $container->get($dep_fqcn);
        }

        return new $fqcn(...$dependencies);
    }
}
