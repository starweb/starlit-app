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
    public function set($key, $value)
    {
        $this->dicValues[$key] = $value;
        unset($this->dicObjects[$key]); // In case an object instance was stored for sharing

        return $this;
    }

    /**
     * Check if a DIC value/object exists.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->dicValues);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasInstance($key)
    {
        return isset($this->dicObjects[$key]);
    }

    /**
     * Get the shared instance of a DIC object, or a DIC value if it's not an object.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new NotFoundException(sprintf('No application value with key "%s" is defined.', $key));
        }

        // Get already instantiated object if it exist
        if (isset($this->dicObjects[$key])) {
            return $this->dicObjects[$key];
        }

        // Check if it's an invokable (closure/anonymous function)
        if (is_object($this->dicValues[$key]) && method_exists($this->dicValues[$key], '__invoke')) {
            $this->dicObjects[$key] = $this->dicValues[$key]($this);

            return $this->dicObjects[$key];
        }

        return $this->dicValues[$key];
    }

    /**
     * Get new instance of a DIC object.
     *
     * @param string $key
     * @return mixed
     */
    public function getNew($key)
    {
        if (!array_key_exists($key, $this->dicValues)) {
            throw new NotFoundException(sprintf('No application value with key "%s" is defined.', $key));
        } elseif (!is_object($this->dicValues[$key]) || !method_exists($this->dicValues[$key], '__invoke')) {
            throw new \InvalidArgumentException(sprintf('Application value "%s" is not invokable.', $key));
        }

        return $this->dicValues[$key]($this);
    }

    /**
     * Destroy a DIC object instance.
     *
     * Will force a new object to be created on next call.
     *
     * @param string $key
     */
    public function destroyInstance($key)
    {
        unset($this->dicObjects[$key]);
    }

    /**
     * Destroy all DIC object instances.
     *
     * Will force new objects to be created on next call.
     */
    public function destroyAllInstances()
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
            $argumentCount = count($arguments);
            if ($argumentCount !== 1) {
                throw new \BadMethodCallException("Invalid argument count[{$argumentCount}] for application {$name}()");
            }

            $key = lcfirst(substr($name, 3));

            return $this->set($key, $arguments[0]);
        } else {
            throw new \BadMethodCallException("No application method named {$name}()");
        }
    }
}
