<?php

/**
 * @package     pine3ree-generic-factories
 * @subpackage  pine3ree-generic-factories-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Container\Factory\Asset;

/**
 * Class Foo
 */
class Foo
{
    private Bar $bar;
    private Baz $baz;

    public function __construct(Bar $bar, Baz $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }

    public function getBar(): Bar
    {
        return $this->bar;
    }

    public function getBaz(): Baz
    {
        return $this->baz;
    }
}
