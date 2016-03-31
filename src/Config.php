<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb / Ehandelslogik i Lund AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Starlit\Utils\Arr;

/**
 * Configuration container.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class Config implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (empty($this->data[$key])) {
            return false;
        }

        return !Arr::allEmpty($this->data[$key]);
    }

    /**
     * Get the specified configuration value.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        return $default;
    }

    /**
     * Get the specified required configuration value.
     *
     * Will throw an exception if not set.
     *
     * @param string $key
     * @return mixed
     */
    public function getRequired($key)
    {
        if (!$this->has($key)) {
            throw new \RuntimeException("Config key \"{$key}\" not found");
        }

        return $this->get($key);
    }

    /**
     * Get all configuration data.
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function offsetSet($key, $value)
    {
        throw new \LogicException('Config is immutable');
    }

    /**
     * Unset a configuration option.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        throw new \LogicException('Config is immutable');
    }
}
