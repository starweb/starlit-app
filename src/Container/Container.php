<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\Container;

use Psr\Container\ContainerInterface;

/**
 * Dependency injection container.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class Container implements ContainerInterface
{
    /**
     * @var array
     */
    private $dicValues = [];

    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $dicObjects = [];

    /**
     * Set a DIC value.
     *
     * Wrap objects provided in a closure for lazy loading.
     *
     * @param string $key
     * @param mixed  $value
     * @return Container
     */
    public function set(string $key, $value): self
    {
        if (!(\is_string($value) || \is_object($value))) {
            throw new \InvalidArgumentException('Value must be a class name, an object instance, or a callable');
        }

        $this->dicValues[$key] = $value;
        unset($this->dicObjects[$key]); // In case an object instance was stored for sharing

        return $this;
    }

    public function alias(string $alias, string $key): self
    {
        $this->aliases[$alias] = $key;

        return $this;
    }

    /**
     * Check if a DIC value/object exists.
     *
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        if (array_key_exists($key, $this->aliases)) {
            $key = $this->aliases[$key];
        }

        return array_key_exists($key, $this->dicValues);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasInstance($key): bool
    {
        if (array_key_exists($key, $this->aliases)) {
            $key = $this->aliases[$key];
        }

        return isset($this->dicObjects[$key]);
    }

    /**
     * Get the shared instance of a DIC object
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->aliases)) {
            $key = $this->aliases[$key];
        }

        // Get already instantiated object if it exist
        if (isset($this->dicObjects[$key])) {
            return $this->dicObjects[$key];
        }

        try {
            if (array_key_exists($key, $this->dicValues)) {
                if (\is_object($this->dicValues[$key])) {
                    // Is it an invokable? (closure/anonymous function)
                    if (method_exists($this->dicValues[$key], '__invoke')) {
                        $instance = $this->dicValues[$key]($this);
                    } else {
                        $instance =  $this->dicValues[$key];
                    }
                } else {
                    $instance = $this->resolveInstance($this->dicValues[$key]);
                }
            } else {
                $instance =  $this->resolveInstance($key);
            }
        } catch (\ReflectionException $e) {
            throw new NotFoundException(sprintf('Key "%s" could not be resolved. ', $key));
        }

        $this->dicObjects[$key] = $instance;

        return $this->dicObjects[$key];
    }

    /**
     * Get a new instance of a DIC object
     *
     * @param string $key
     * @return mixed
     */
    public function getNew(string $key)
    {
        if (array_key_exists($key, $this->aliases)) {
            $key = $this->aliases[$key];
        }

        try {
            if (array_key_exists($key, $this->dicValues)) {
                if (\is_object($this->dicValues[$key])) {
                    // Is it an invokable? (closure/anonymous function)
                    if (method_exists($this->dicValues[$key], '__invoke')) {
                        return $this->dicValues[$key]($this);
                    }
                    throw new \LogicException('The value for the specified key is a pre-made instance');
                }
                return $this->resolveInstance($this->dicValues[$key]);
            }
            return $this->resolveInstance($key);
        } catch (\ReflectionException $e) {
            throw new NotFoundException(sprintf('Key "%s" could not be resolved.', $key));
        }
    }

    /**
     * Destroy a DIC object instance.
     *
     * Will force a new object to be created on next call.
     *
     * @param string $key
     */
    public function destroyInstance($key): void
    {
        unset($this->dicObjects[$key]);
    }

    /**
     * Destroy all DIC object instances.
     *
     * Will force new objects to be created on next call.
     */
    public function destroyAllInstances(): void
    {
        $this->dicObjects = [];

        // To make sure objects (like database connections) are destructed properly. PHP might not destruct objects
        // until the end of execution otherwise.
        gc_collect_cycles();
    }

    /**
     * Magic method to get or set DIC values.
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // getNew followed by an upper letter like getNewApple()
        if (preg_match('/^getNew([A-Z].*)/', $name, $matches)) {
            $key = lcfirst($matches[1]);

            return $this->getNew($key);
        } elseif (strpos($name, 'get') === 0) {
            $key = lcfirst(substr($name, 3));

            return $this->get($key);
        } elseif (strpos($name, 'set') === 0) {
            $argumentCount = \count($arguments);
            if ($argumentCount !== 1) {
                throw new \BadMethodCallException("Invalid argument count[{$argumentCount}] for application {$name}()");
            }

            $key = lcfirst(substr($name, 3));

            return $this->set($key, $arguments[0]);
        } else {
            throw new \BadMethodCallException("No application method named {$name}()");
        }
    }

    /**
     * Instantiate an object of named class, recursively resolving dependencies
     *
     * @param string $className Fully qualified class name
     * @return mixed
     * @throws \ReflectionException
     */
    protected function resolveInstance(string $className)
    {
        $class = new \ReflectionClass($className);

        if (!$class->isInstantiable()) {
            throw new \ReflectionException(sprintf('Class %s cannot be instantiated', $className));
        }

        $parameterValues = [];
        if (($constructor = $class->getConstructor())) {
            $parameterValues = $this->resolveParameters(
                $constructor->getParameters()
            );
        }

        return $class->newInstanceArgs($parameterValues);
    }

    /**
     * Recursively resolve function parameters using type hints
     *
     * @param \ReflectionParameter[]
     * @return mixed
     */
    protected function resolveParameters(array $parameters): array
    {
        $values = [];

        /**
         * @var \ReflectionParameter $parameter
         */
        foreach ($parameters as $parameter) {
            if (($parameterClass = $parameter->getClass())) {
                try {
                    $values[] = $this->get($parameterClass->getName());
                }
                catch (NotFoundException $e) { // We're probably dealing with an unmapped interface here
                    $values[] = $parameter->getDefaultValue();
                }
            } else {
                $values[] = $parameter->getDefaultValue();
            }
        }

        return $values;
    }
}
