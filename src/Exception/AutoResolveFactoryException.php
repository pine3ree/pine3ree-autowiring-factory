<?php

/**
 * @package pine3ree-auto-resolve-factory
 * @author  pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\Container\Factory\Exception;

use RuntimeException;
use Throwable;

class AutoResolveFactoryException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
