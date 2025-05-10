<?php

/**
 * @package     pine3ree-generic-factories
 * @subpackage  pine3ree-generic-factories-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Container\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use RuntimeException;
use SplObjectStorage;
use pine3ree\Container\Factory\ReflectionBasedFactory;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\test\Container\Factory\Asset\Bar;
use pine3ree\test\Container\Factory\Asset\Bat;
use pine3ree\test\Container\Factory\Asset\Baz;
use pine3ree\test\Container\Factory\Asset\Foo;

use function array_pop;

class ReflectionBasedFactoryTest extends TestCase
{
    private ContainerInterface $container;

    private ReflectionBasedFactory $factory;
    private ParamsResolverInterface $paramsResolver;

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
        $this->paramsResolver = $this->getMockBuilder(ParamsResolverInterface::class)->getMock();

        $this->factory = new ReflectionBasedFactory();

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
            [ParamsResolverInterface::class, true],
        ];

        $this->valReturnMap = [
            [Bar::class, $this->bar],
            [Baz::class, $this->baz],
            [Bat::class, $this->bat],
            ['bar', $this->bar],
            ['baz', $this->baz],
            ['bat', $this->bat],
            [ParamsResolverInterface::class, $this->paramsResolver],
        ];

        $this->container->method('has')->willReturnMap($this->hasReturnMap);
        $this->container->method('get')->willReturnMap($this->valReturnMap);

        $this->paramsResolver->method('resolve')->willReturnMap([
            [[Foo::class, '__construct'], null, [$this->container->get(Bar::class), $this->container->get(Baz::class)]],
            [[Foo::class, '__construct'], [Bar::class => $this->bar, Baz::class => $this->baz], [$this->bar, $this->baz]],
            [[Bar::class, '__construct'], null, []],
            [[Baz::class, '__construct'], null, []],
            [[Bat::class, '__construct'], null, []],
            [['bar', '__construct'], null, []],
            [['baz', '__construct'], null, []],
            [['bat', '__construct'], null, []],
        ]);
    }

    public function testThatConstructorLessClassesAreInstantiated()
    {
        $baz =  ($this->factory)($this->container, Baz::class);
        self::assertInstanceOf(Baz::class, $baz);
    }

    public function testThatNonexistentClassRaisesException()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $this->expectException(RuntimeException::class);
        ($this->factory)($container, NonEXistentClass::class);
    }

    public function testThatPrivateConstructorRaisesException()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $this->expectException(RuntimeException::class);
        ($this->factory)($container, Bat::class);
    }

    public function testThatClassesWithNoDependenciesAreInstantiated()
    {
        $obj = ($this->factory)($this->container, Baz::class);
        self::assertInstanceOf(Baz::class, $obj);
    }

    public function testThatClassesWithResolvableDependenciesAreInstantiated()
    {
        $fqcn = Foo::class;
        $obj = ($this->factory)($this->container, $fqcn);
        self::assertInstanceOf($fqcn, $obj);
    }

    public function testThatNonResolvableDependenciesRaiseExceptions()
    {
        $fqcn = Foo::class;

        $paramsResolver = $this->getMockBuilder(ParamsResolverInterface::class)->getMock();
        $paramsResolver->method('resolve')->willReturnMap([
            [[$fqcn, '__construct'], null, []],
        ]);

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap([
            [Bar::class, false],
            [Baz::class, false],
            ['bar', false],
            ['baz', false],
            [ParamsResolverInterface::class, true],
        ]);
        $container->method('get')->willReturnMap([
            [ParamsResolverInterface::class, $paramsResolver],
        ]);

        $this->expectException(RuntimeException::class);
        ($this->factory)($container, $fqcn);
    }

    public function testThatParamsResolversAreCached()
    {
        $fqcn = Bar::class;

        self::assertNull($this->getCachedParamsResolver($this->factory, $this->container));

        $obj = ($this->factory)($this->container, $fqcn);

        $paramsResolver = $this->getCachedParamsResolver($this->factory, $this->container);

        self::assertInstanceOf(ParamsResolverInterface::class, $paramsResolver);

        $obj = ($this->factory)($this->container, $fqcn);

        self::assertSame($paramsResolver, $this->getCachedParamsResolver($this->factory, $this->container));
    }

    public function testThatAParamsResolverIsCreatedAndCachedIfNotFoundInContainer()
    {
        $fqcn = Bar::class;

        $hasReturnMap = $this->hasReturnMap;
        array_pop($hasReturnMap);
        $hasReturnMap[] = [ParamsResolverInterface::class, false];

        $valReturnMap = $this->valReturnMap;
        array_pop($valReturnMap);

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('has')->willReturnMap($hasReturnMap);
        $container->method('get')->willReturnMap($valReturnMap);

        self::assertNull($this->getCachedParamsResolver($this->factory, $container));

        $obj = ($this->factory)($container, $fqcn);

        $paramsResolver = $this->getCachedParamsResolver($this->factory, $container);

        self::assertInstanceOf(ParamsResolverInterface::class, $paramsResolver);

        $obj = ($this->factory)($container, $fqcn);

        self::assertSame($paramsResolver, $this->getCachedParamsResolver($this->factory, $container));
    }

    private function getCachedParamsResolver(
        ?ReflectionBasedFactory $factory = null,
        ?ContainerInterface $container = null
    ): ?ParamsResolverInterface {
        $factory = $factory ?? $this->factory;
        $container = $container ?? $this->container;

        $cacheProp = new ReflectionProperty($factory, 'cache');
        $cacheProp->setAccessible(true);
        $cache = $cacheProp->getValue($factory);

        if ($cache instanceof SplObjectStorage) {
            if ($cache->contains($container)) {
                return $cache->offsetGet($container);
            }
        }

        return null;
    }
}
