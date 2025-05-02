# pine3ree generic factories

[![Continuous Integration](https://github.com/pine3ree/pine3ree-generic-factories/actions/workflows/continuos-integration.yml/badge.svg)](https://github.com/pine3ree/pine3ree-generic-factories/actions/workflows/continuos-integration.yml)

This package provides a reflection-based factory, which is based on the
`pine3ree-params-resolver` package and a configuration-based factory with
dependencies specified inside configuration

Example for reflection-based factory:

```php
<?php

use My\App\Model\PostMapper;
use Psr\Container\ContainerInterface;
use pine3ree\Container\Factory\ReflectionBasedFactory;

$container = include("config/container.php");
$factory = new ReflectionBasedFactory(); // Need just one instance

// All dependencies of PostMapper are resolved by the factory if found in the
// container
$postMapper = $factory($container, PostMapper::class);

```

<br>

Example for configuration-based factory:

```php
<?php

use My\App\Model\Database\Db;
use My\App\Model\Database\PdoFactory;
use My\App\Model\PostMapper;
use PDO;
use Psr\Container\ContainerInterface;
use pine3ree\Container\Factory\ConfigurationBasedFactory;

$container = include("config/container.php");
$factory = new ReflectionBasedFactory(); // Need just one instance

$config = $container->get('config');
// The 'config' service in the container should return an array like the
// one below:
$config = [
    //...
    'dependencies' => [
        'factories' => [
            //...
            PDO::class => PdoFactory::class, // Ad-hoc factory
            //...
        ],
        // Configuration key for dependencies built by the config-based factory
        ConfigurationBasedFactory:class => [
            // Just list the dependencies class names or container service-ids
            // for each managed class
            Db::class => [
                PDO::class, // Resolved by a custom factory
            ],
            PostMapper::class => [
                Db::class, // Resolved by the config-based factory as well
                'config', // THe 'config' array will be injected as 2nd argument
            ],
        ],
    ],
    //...
];

$postMapper = $factory($container, PostMapper::class);

```
