<?php

/**
 * @package     pine3ree-generic-factories
 * @subpackage  pine3ree-generic-factories-subpackage
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
