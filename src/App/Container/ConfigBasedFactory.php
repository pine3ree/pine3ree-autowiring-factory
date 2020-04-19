<?php

/**
 * @author pine3ree https://github.com/pine3ree
 * @package p3im
 * @subpackage p3im-action
 */

namespace App\Container;

use RuntimeException;
use Throwable;
use Psr\Container\ContainerInterface;

use function sprintf;

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
        foreach ($config as $depFQCN) {
            if ($depFQCN === ContainerInterface::class) {
                $dependencies[] = $container;
                continue;
            }
            if (!class_exists($depFQCN)) {
                throw new RuntimeException(
                    "Unable to load the dependency class `{$depFQCN}`!"
                );
            }
            if (!$container->has($depFQCN)) {
                throw new RuntimeException(
                    "Unable to load the dependency `{$depFQCN}` for class `{$fqcn}`!"
                );
            }
            $dependencies[] = $container->get($depFQCN);
        }

        return new $fqcn(...$dependencies);
    }
}
