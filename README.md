# pine3ree autowiring factory

[![Continuous Integration](https://github.com/pine3ree/pine3ree-autowiring-factory/actions/workflows/continuos-integration.yml/badge.svg)](https://github.com/pine3ree/pine3ree-autowiring-factory/actions/workflows/continuos-integration.yml)

This package provides an autowiring reflection-based factory, which operates
using the [`pine3ree-params-resolver`](https://github.com/pine3ree/pine3ree-params-resolver) library

Example:

```php

// file: src/My/App/Model/PostMapper.php

use My\App\Database\DbInterface;
use My\App\Database\Db\HydratorInterface;
use My\App\Configuration\Config;

class PostMapper
{
    //...

    public function __construct(DbInterface $db, HydratorInterface $hydrator)
    {
        // ... inject deps here
    }
}

/// ---

// file: test.php

use My\App\Model\PostMapper;
use Psr\Container\ContainerInterface;
use pine3ree\Container\Factory\AutowiringFactory;

$container = include("config/container.php");

$factory = new AutowiringFactory(); // We need just one instance of it

// All dependencies of PostMapper are resolved by the factory if they are found
// in the container, i.e. if:
// - $container->get(DbInterface::class) returns a DbInterface object
// - $container->get(HydratorInterface::class) returns a HydratorInterface object

$postMapper = $factory($container, PostMapper::class);

```
If the container configuration for the service `PostMapper::class` instructs
the container to use the autowiring-factory, you can just fetch the service instance
from the container:
```php
$postMapper = $container->get(PostMapper::class);
```
