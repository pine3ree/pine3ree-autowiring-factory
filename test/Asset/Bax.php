<?php

/**
 * @package     pine3ree-autowiring-factory
 * @subpackage  pine3ree-autowiring-factory-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Container\Factory\Asset;

use Psr\Container\ContainerInterface;

class Bax
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
