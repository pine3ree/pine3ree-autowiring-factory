# pine3ree auto-resolve factory

[![Continuous Integration](https://github.com/pine3ree/pine3ree-auto-resolve-factory/actions/workflows/continuos-integration.yml/badge.svg)](https://github.com/pine3ree/pine3ree-auto-resolve-factory/actions/workflows/continuos-integration.yml)

This package provides an autowiring reflection-based factory, which operates
using the [`pine3ree-params-resolver`](https://github.com/pine3ree/pine3ree-params-resolver)
library

Example:

```php

// file: src/My/App/Model/PostMapper.php

use My\App\Database\ConnectionInterface;
use My\App\Database\HydratorInterface;
//...

class PostMapper
{
    ConnectionInterface $db;
    HydratorInterface $hydrator;

    public function __construct(ConnectionInterface $db, HydratorInterface $hydrator)
    {
        $this->db = $db;
        $this->hydrator = $hydrator;
    }

    //...
}

//------------------------------------------------------------------------------

// file: test.php

use My\App\Model\PostMapper;
use Psr\Container\ContainerInterface;
use pine3ree\Container\Factory\AutoResolveFactory;

$container = include("config/container.php");

$factory = new AutoResolveFactory(); // We need just one instance of it

// All dependencies of PostMapper are resolved by the factory if they are found
// in the container, i.e. if:
// - $container->get(ConnectionInterface::class) returns a ConnectionInterface object
// - $container->get(HydratorInterface::class) returns a HydratorInterface object

$postMapper = $factory($container, PostMapper::class);

```

If the container configuration for the service `PostMapper::class` instructs
the container to use the auto-resolve-factory, you can just fetch the service instance
from the container:
```php
$postMapper = $container->get(PostMapper::class);
```

The requested service constructor's arguments are resolved in the following way:

1. If the argument is a type-hinted dependency with a fully-qualified-class/interface
   name the factory tries to load a dependency registered in the container with
   that class-string as identifier.
   If no such dependency is found, the factory will try the default provided value,
   if any, then the `null` value if the argument is nullable, and eventually it will
   try to instantiate the class directly. An exception is thrown on failure.

1. If the argument is not type-hinted or is type-hinted with a builtin type the
   factory will try to load a service or a parameter value registered in the
   container with the parameter name as identifier.
   If not found, the factory will try the default provided value, if any, then
   the `null` value if the argument is nullable, otherwise an exception is thrown.
