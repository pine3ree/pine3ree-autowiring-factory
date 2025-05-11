<?php

/**
 * @package     pine3ree-auto-resolving-factory
 * @subpackage  pine3ree-auto-resolving-factory-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Container\Factory\Asset;

class Bat
{
    private function __construct()
    {
    }

    public static function createInstance(): self
    {
        return new self();
    }
}
