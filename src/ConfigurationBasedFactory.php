<?php

/**
 * @package pine3ree-abstract-factories
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Container\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

use function get_class;
use function is_string;
use function method_exists;

/**
 * A generic factory that resolves and injects dependencies from configuration
 *
 * Note: all dependencies must be explicitly defined and sorted accordingly to the
 * class constructor signature
 *
 * The $config array must be stored in the container and will be searched using the following keys
 *
 * $config['config|configuration']['dependencies']['pine3ree\Container\ConfigBasedFactory'][$fqcn]
 * $config['config|configuration']['pine3ree\Container\ConfigBasedFactory'][$fqcn]
 */
class ConfigurationBasedFactory
{
    public function __invoke(ContainerInterface $container, string $fqcn): object
    {
        if (!method_exists($fqcn, '__construct')) {
            return new $fqcn();
        }

        // Try 'config' and 'configuration' keys
        if ($container->has('config')) {
            $config = $container->get('config');
        } elseif ($container->has('configuration')) {
            $config = $container->get('configuration');
        } else {
            $config = null;
        }

        // Try nested and direct configuration key
        $fqcn_dependency_config = $config['dependencies'][static::class][$fqcn]
            ?? $config[static::class][$fqcn]
            ?? null;

        if (empty($fqcn_dependency_config)) {
            try {
                return new $fqcn();
            } catch (Throwable $ex) {
                throw new RuntimeException(
                    "Mandatory factory configuration not found for class `{$fqcn}`!"
                );
            }
        }

        $dependencies = [];
        foreach ($fqcn_dependency_config as $dep_name) {
            if (!is_string($dep_name)) {
                throw new RuntimeException(
                    "Configured dependency names for class `{$fqcn}` must be of type `string`!"
                );
            } elseif ($dep_name === ContainerInterface::class || $dep_name === get_class($container)) {
                $dependencies[] = $container;
            } elseif ($container->has($dep_name)) {
               $dependencies[] = $container->get($dep_name);
            } else {
                throw new RuntimeException(
                    "Unable to load the dependency `{$dep_name}` for class `{$fqcn}`!"
                );
            }
        }

        try {
            return new $fqcn(...$dependencies);
        } catch (Throwable $ex) {
            throw new RuntimeException(
                "Unable to instantiate an object of class `{$fqcn}` with provided configuration"
            );
        }
    }
}
