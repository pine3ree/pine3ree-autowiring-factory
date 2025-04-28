<?php

/**
 * @package     pine3ree-abstract-factories
 * @subpackage  pine3ree-abstract-factories-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Container\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;
use pine3ree\Container\Factory\ConfigurationBasedFactory;
use pine3ree\test\Container\Factory\Asset\Bar;
use pine3ree\test\Container\Factory\Asset\Bat;
use pine3ree\test\Container\Factory\Asset\Bax;
use pine3ree\test\Container\Factory\Asset\Baz;
use pine3ree\test\Container\Factory\Asset\Foo;

class ConfigurationBasedFactoryTest extends TestCase
{
    private ContainerInterface $container;

    private ConfigurationBasedFactory $factory;

    private Bar $bar;
    private Baz $baz;

    private array $hasReturnMap = [];
    private array $valReturnMap = [];

    /**
     * set up test environmemt
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $this->factory = new ConfigurationBasedFactory();

        $this->bar = new Bar();
        $this->baz = new Baz();
        $this->bat = Bat::createInstance();

        $this->hasReturnMap = [
            [Bar::class, true],
            [Baz::class, true],
            [Bat::class, true],
            ['bar', true],
            ['baz', true],
            ['bat', true],
            ['not', false],
        ];

        $this->valReturnMap = [
            [Bar::class, $this->bar],
            [Baz::class, $this->baz],
            [Bat::class, $this->bat],
            ['bar', $this->bar],
            ['baz', $this->baz],
            ['bat', $this->bat],
        ];

        $this->container->method('has')->willReturnMap($this->hasReturnMap);
        $this->container->method('get')->willReturnMap($this->valReturnMap);
    }

    /**
     * @dataProvider provideValidConfigurations
     */
    public function testThatFactoryWorksWithValidConfigurations(string $config_key, string $alt_config_key, array $config_array)
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = [$config_key, true];
        $hasReturnMap[] = [$alt_config_key, false];
        $valReturnMap = $this->valReturnMap;
        $valReturnMap[] = [$config_key, $config_array];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);
        $container->method('get')->willReturnMap($valReturnMap);

        $foo = ($this->factory)($container, Foo::class);

        self::assertInstanceOf(Foo::class, $foo);
    }

    public function provideValidConfigurations(): array
    {
        $factory_config_indexed_by_fqcn = [
            Foo::class => [
                Bar::class,
                Baz::class,
            ],
        ];
        $factory_config_indexed_by_name = [
            Foo::class => [
                'bar',
                'baz',
            ],
        ];

        $config_dependencies_by_fqcn = [
            'dependencies' => [
                ConfigurationBasedFactory::class => $factory_config_indexed_by_fqcn,
            ],
        ];
        $config_dependencies_by_name = [
            'dependencies' => [
                ConfigurationBasedFactory::class => $factory_config_indexed_by_name,
            ],
        ];

        $config_direct_by_fqcn = [
            ConfigurationBasedFactory::class => $factory_config_indexed_by_fqcn
        ];
        $config_direct_by_name = [
            ConfigurationBasedFactory::class => $factory_config_indexed_by_name
        ];

        // [provided_key, missing_key, configuration_array_unser_provided_key]
        return [
            ['config', 'configuration', $config_dependencies_by_fqcn],
            ['config', 'configuration', $config_dependencies_by_name],
            ['config', 'configuration', $config_direct_by_fqcn],
            ['config', 'configuration', $config_direct_by_name],
            ['configuration', 'config', $config_dependencies_by_fqcn],
            ['configuration', 'config', $config_dependencies_by_name],
            ['configuration', 'config', $config_direct_by_fqcn],
            ['configuration', 'config', $config_direct_by_name],
        ];
    }

    public function testThatConstructorLessClassesAreInstantiated()
    {
        $baz =  ($this->factory)($this->container, Baz::class);
        self::assertInstanceOf(Baz::class, $baz);
    }

    public function testThatPrivateConstructorClassesWithNoDependenciesRaiseException()
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = ['config', false];
        $hasReturnMap[] = ['configuration', false];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);

        $this->expectException(RuntimeException::class);
        ($this->factory)($container, Bat::class);
//        self::assertInstanceOf(Baz::class, $baz);
    }

    public function testThatClassesWithNoDependenciesAreInstantiated()
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = ['config', false];
        $hasReturnMap[] = ['configuration', false];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);

        $bar = ($this->factory)($container, Bar::class);
        self::assertInstanceOf(Bar::class, $bar);
    }

    public function testThatClassesWithNoDependencyConfigurationRaiseException()
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = ['config', false];
        $hasReturnMap[] = ['configuration', false];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);

        $this->expectException(RuntimeException::class);
        ($this->factory)($container, Foo::class);
    }

    public function testThatClassesWithInvalidDependencyConfigurationRaiseException()
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = ['config', true];
        $valReturnMap = $this->valReturnMap;
        $valReturnMap[] = ['config', [
            'dependencies' => [
                ConfigurationBasedFactory::class => [
                    Foo::class => [
                        42, // invalid: must be a string
                    ],
                ],
            ],
        ]];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);
        $container->method('get')->willReturnMap($valReturnMap);

        $this->expectException(RuntimeException::class);
        ($this->factory)($container, Foo::class);
    }

    public function testThatClassesWithContainerAsDependencyAreProvidedWithContainer()
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = ['config', true];
        $valReturnMap = $this->valReturnMap;
        $valReturnMap[] = ['config', [
            'dependencies' => [
                ConfigurationBasedFactory::class => [
                    Bax::class => [
                        ContainerInterface::class,
                    ],
                ],
            ],
        ]];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);
        $container->method('get')->willReturnMap($valReturnMap);

        $bax = ($this->factory)($container, Bax::class);
        self::assertInstanceOf(Bax::class, $bax);
        self::assertInstanceOf(ContainerInterface::class, $bax->getContainer());
        self::assertSame($container, $bax->getContainer());
    }

    public function testThatClassesWithIncompleteDependencyConfigurationRaiseException()
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = ['config', true];
        $valReturnMap = $this->valReturnMap;
        $valReturnMap[] = ['config', [
            'dependencies' => [
                ConfigurationBasedFactory::class => [
                    Foo::class => [
                        Bar::class,
                        //Baz::class, // missing dependency
                    ],
                ],
            ],
        ]];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);
        $container->method('get')->willReturnMap($valReturnMap);

        $this->expectException(Throwable::class);
        ($this->factory)($container, Foo::class);
    }

    public function testThatClassesWithMissingDependencyRaiseException()
    {
        $hasReturnMap = $this->hasReturnMap;
        $hasReturnMap[] = ['config', true];
        $valReturnMap = $this->valReturnMap;
        $valReturnMap[] = ['config', [
            'dependencies' => [
                ConfigurationBasedFactory::class => [
                    Foo::class => [
                        'bar',
                        'not', // not in container
                    ],
                ],
            ],
        ]];

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);
        $container->method('get')->willReturnMap($valReturnMap);

        $this->expectException(Throwable::class);
        ($this->factory)($container, Foo::class);
    }
}
