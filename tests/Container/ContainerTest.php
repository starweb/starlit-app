<?php declare(strict_types=1);

namespace Starlit\App\Container;

use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    /**
     * @covers \Starlit\App\Container\Container::set
     */
    public function testSetString(): void
    {
        $this->container->set('foo', \stdClass::class);

        $this->assertTrue($this->container->has('foo'));
        $this->assertInstanceOf(\stdClass::class, $this->container->get('foo'));
    }

    /**
     * @covers \Starlit\App\Container\Container::set
     */
    public function testSetObject(): void
    {
        $object = new \stdClass();
        $this->container->set('stdClass', $object);

        $this->assertTrue($this->container->has('stdClass'));
        $this->assertInstanceOf(\stdClass::class, $this->container->get('stdClass'));
    }

    /**
     * @covers \Starlit\App\Container\Container::set
     */
    public function testSetObjectWithInvalidValueShouldThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be a class name, an object instance, or a callable');
        $this->container->set('int', 123);
    }

    /**
     * @covers \Starlit\App\Container\Container::unset
     */
    public function testUnsetObject(): void
    {
        $this->container->set('foo', 'bar');

        $this->assertTrue($this->container->has('foo'));
        $this->container->unset('foo');
        $this->assertFalse($this->container->has('foo'));
    }

    /**
     * @covers \Starlit\App\Container\Container::alias
     */
    public function testAlias(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $this->container->alias('bar', 'foo');

        $this->assertTrue($this->container->has('bar'));
        $this->assertSame(\stdClass::class, \get_class($this->container->get('bar')));
    }

    /**
     * @covers \Starlit\App\Container\Container::unalias
     */
    public function testUnalias(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $this->container->alias('bar', 'foo');
        $this->assertTrue($this->container->has('bar'));
        $this->container->unalias('bar');
        $this->assertFalse($this->container->has('bar'));
    }

    /**
     * @covers \Starlit\App\Container\Container::has
     */
    public function testHas(): void
    {
        $this->container->set('foo', 'bar');
        $this->assertTrue($this->container->has('foo'));
    }

    /**
     * @covers \Starlit\App\Container\Container::has
     */
    public function testHasWithAlias(): void
    {
        $this->container->set('bar', 'bar');
        $this->container->alias('foo', 'bar');
        $this->assertTrue($this->container->has('foo'));
    }

    /**
     * @covers \Starlit\App\Container\Container::hasInstance
     */
    public function testHasInstance(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $this->assertFalse($this->container->hasInstance('foo'));
        $this->container->get('foo');
        $this->assertTrue($this->container->hasInstance('foo'));
    }

    /**
     * @covers \Starlit\App\Container\Container::hasInstance
     */
    public function testHasInstanceWithAlias(): void
    {
        $object = new \stdClass();
        $this->container->set('bar', $object);
        $this->container->alias('foo', 'bar');
        $this->assertFalse($this->container->hasInstance('foo'));
        $this->container->get('foo');
        $this->assertTrue($this->container->hasInstance('foo'));
    }

    /**
     * @covers \Starlit\App\Container\Container::get
     */
    public function testGet(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $instance = $this->container->get('foo');
        $this->assertSame($instance, $object);
    }

    /**
     * @covers \Starlit\App\Container\Container::get
     */
    public function testGetWithAlias(): void
    {
        $object = new \stdClass();
        $this->container->set('bar', $object);
        $this->container->alias('foo', 'bar');
        $instance = $this->container->get('foo');
        $this->assertSame($instance, $object);
    }

    /**
     * @covers \Starlit\App\Container\Container::get
     */
    public function testGetAlreadyInstantiated(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $instance = $this->container->get('foo');
        $instance2 = $this->container->get('foo');
        $this->assertSame($instance, $instance2);
    }

    /**
     * @covers \Starlit\App\Container\Container::get
     */
    public function testGetWithInvalidKeyThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Key "foo" could not be resolved.');
        $this->container->get('foo');
    }

    /**
     * @covers \Starlit\App\Container\Container::getNew
     */
    public function testGetNew(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $instance = $this->container->getNew('foo');
        $this->assertSame($instance, $object);
    }

    /**
     * @covers \Starlit\App\Container\Container::getNew
     */
    public function testGetNewWithoutSetting(): void
    {
        $instance = $this->container->getNew(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * @covers \Starlit\App\Container\Container::getNew
     */
    public function testGetNewWithAlias(): void
    {
        $object = new \stdClass();
        $this->container->set('bar', $object);
        $this->container->alias('foo', 'bar');
        $instance = $this->container->getNew('foo');
        $this->assertSame($instance, $object);
    }

    /**
     * @covers \Starlit\App\Container\Container::getNew
     */
    public function testGetNewWithInvalidKeyThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Key "foo" could not be resolved.');
        $this->container->getNew('foo');
    }

    /**
     * @covers \Starlit\App\Container\Container::getValueInstance
     */
    public function testGetValueInstanceWithObject(): void
    {
        $this->container->set(\stdClass::class, new \stdClass());
        $method = $this->getInvokableGetValueInstanceReflectionMethod($this->container);
        $instance = $method->invoke($this->container, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * @covers \Starlit\App\Container\Container::getValueInstance
     */
    public function testGetValueInstanceWithFullyQualifiedClassName(): void
    {
        $this->container->set(\stdClass::class, \stdClass::class);
        $method = $this->getInvokableGetValueInstanceReflectionMethod($this->container);
        $instance = $method->invoke($this->container, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * @covers \Starlit\App\Container\Container::getValueInstance
     */
    public function testGetValueInstanceWithInvokableCallback(): void
    {
        $this->container->set(\stdClass::class, function() {
            return new \stdClass();
        });
        $method = $this->getInvokableGetValueInstanceReflectionMethod($this->container);
        $instance = $method->invoke($this->container, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * @covers \Starlit\App\Container\Container::destroyInstance
     */
    public function testDestroyInstance(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $this->assertFalse($this->container->hasInstance('foo'));
        $this->container->get('foo'); // creates the instance
        $this->assertTrue($this->container->hasInstance('foo'));
        $this->container->destroyInstance('foo');
        $this->assertFalse($this->container->hasInstance('foo'));
    }

    /**
     * @covers \Starlit\App\Container\Container::destroyInstance
     */
    public function testDestroyInstanceWithAlias(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $this->container->alias('bar', 'foo');
        $this->assertFalse($this->container->hasInstance('bar'));
        $this->container->get('bar'); // creates the instance
        $this->assertTrue($this->container->hasInstance('bar'));
        $this->container->destroyInstance('bar');
        $this->assertFalse($this->container->hasInstance('bar'));
    }

    /**
     * @covers \Starlit\App\Container\Container::destroyAllInstances
     */
    public function testDestroyAllInstance(): void
    {
        $object = new \stdClass();
        $this->container->set('foo', $object);
        $this->container->set('bar', $object);
        $this->assertFalse($this->container->hasInstance('foo'));
        $this->assertFalse($this->container->hasInstance('bar'));
        $this->container->get('foo'); // creates the instances
        $this->container->get('bar');
        $this->assertTrue($this->container->hasInstance('foo'));
        $this->assertTrue($this->container->hasInstance('bar'));
        $this->container->destroyAllInstances();
        $this->assertFalse($this->container->hasInstance('foo'));
        $this->assertFalse($this->container->hasInstance('bar'));
    }

    /**
     * @covers \Starlit\App\Container\Container::__call
     */
    public function testCallGetNewFooThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Key "foo" could not be resolved.');

        $this->container->getNewFoo();
    }

    /**
     * @covers \Starlit\App\Container\Container::__call
     */
    public function testCallGetFooThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Key "foo" could not be resolved.');

        $this->container->getFoo();
    }

    /**
     * @covers \Starlit\App\Container\Container::__call
     */
    public function testCallSetFooWithoutArgumentsThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Invalid argument count[0] for application setFoo()');

        $this->container->setFoo();
    }

    /**
     * @covers \Starlit\App\Container\Container::__call
     */
    public function testCallSetFoo(): void
    {
        $this->container->setFoo(\stdClass::class);
        $this->assertTrue($this->container->has('foo'));
        $instance = $this->container->get('foo');
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * @covers \Starlit\App\Container\Container::__call
     */
    public function testCallFooThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('No application method named foo()');
        $this->container->foo();
    }

    /**
     * @covers \Starlit\App\Container\Container::resolveInstance
     */
    public function testResolveInstance(): void
    {
        $method = $this->getInvokableResolveInstanceReflectionMethod();
        $instance = $method->invoke($this->container, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * @covers \Starlit\App\Container\Container::resolveInstance
     */
    public function testResolveInstanceWithNotInstantiableClass(): void
    {
        $method = $this->getInvokableResolveInstanceReflectionMethod();
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage('Class Throwable cannot be instantiated');
        $method->invoke($this->container, \Throwable::class); // interfaces are not instantiable classes
    }

    /**
     * @covers \Starlit\App\Container\Container::resolveInstance
     */
    public function testResolveInstanceWithClassThatHasConstructorArguments(): void
    {
        $class = (new class(new \stdClass()) extends \stdClass {
            public function __construct(\stdClass $foo)
            {
            }
        });
        $method = $this->getInvokableResolveInstanceReflectionMethod();
        $instance = $method->invoke($this->container, \get_class($class));

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    public function testResolveParameters(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
    }

    public function testResolveParametersWithPredefinedClassValue(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, [\stdClass::class => 'foo']);
        $this->assertSame('foo', $resolved[0]);
    }

    public function testResolveParametersWithPredefinedValueWithoutType(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass, $withoutType): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, ['withoutType' => 'foo']);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
        $this->assertSame('foo', $resolved[1]);
    }

    public function testResolveParametersWithPredefinedValueWithIntTypePassedAsStringStartingWithNumber(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass, int $int): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, ['int' => '123 and a string']);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
        $this->assertIsInt($resolved[1]);
        $this->assertSame(123, $resolved[1]);
    }

    public function testResolveParametersWithPredefinedValueWithIntTypePassedAsString(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass, int $int): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, ['int' => 'a string']);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
        $this->assertIsInt($resolved[1]);
        $this->assertSame(0, $resolved[1]);
    }

    public function testResolveParametersWithPredefinedValueWithArrayTypePassedAsString(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass, array $array): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, ['array' => 'a string']);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
        $this->assertIsArray($resolved[1]);
        $this->assertSame(['a string'], $resolved[1]);
    }

    public function testResolveParametersWithPredefinedValueWithArrayTypePassedAsObject(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass, array $array): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, ['array' => new \stdClass()]);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
        $this->assertIsArray($resolved[1]);
        $this->assertSame([], $resolved[1]);
    }

    public function testResolveParametersWithPredefinedValueWithArrayTypePassedAsArray(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass, array $array): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, ['array' => ['foo' => 'bar']]);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
        $this->assertIsArray($resolved[1]);
        $this->assertSame(['foo' => 'bar'], $resolved[1]);
    }

    public function testResolveParametersWithPredefinedValueWithFloatType(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\stdClass $stdClass, float $float): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters, ['float' => 20]);
        $this->assertInstanceOf(\stdClass::class, $resolved[0]);
        $this->assertIsFloat($resolved[1]);
        $this->assertSame(20.0, $resolved[1]);
    }

    public function testResolveParametersWithDefaultValue(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(int $iterations = 3): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters);
        $this->assertSame(3, $resolved[0]);
    }

    public function testResolveParametersWithNotFoundClass(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\Throwable $foo): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Key "Throwable" could not be resolved.');
        $this->container->resolveParameters($parameters);
    }

    public function testResolveParametersWithNotFoundClassAndDefaultValue(): void
    {
        $class = (new class() extends \stdClass {
            public function foo(\Throwable $foo = null): void
            {
            }
        });
        $reflected = new \ReflectionClass($class);
        $function = $reflected->getMethod('foo');
        $parameters = $function->getParameters();

        $resolved = $this->container->resolveParameters($parameters);
        $this->assertNull($resolved[0]);
    }

    protected function getInvokableGetValueInstanceReflectionMethod(Container $container): \ReflectionMethod
    {
        $reflection = new \ReflectionClass($container);
        $method = $reflection->getMethod('getValueInstance');
        $method->setAccessible(true);

        return $method;
    }

    protected function getInvokableResolveInstanceReflectionMethod(): \ReflectionMethod
    {
        $reflection = new \ReflectionClass($this->container);
        $method = $reflection->getMethod('resolveInstance');
        $method->setAccessible(true);

        return $method;
    }
}
