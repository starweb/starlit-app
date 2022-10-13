<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Starlit\Utils\Arr;

class Config implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function has(string $key): bool
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
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        return $default;
    }

    /**
     * Get the specified required configuration value.
     * Will throw an exception if not set.
     *
     * @param string $key
     * @return mixed
     */
    public function getRequired(string $key)
    {
        if (!$this->has($key)) {
            throw new \RuntimeException("Config key \"{$key}\" not found");
        }

        return $this->get($key);
    }

    public function all(): array
    {
        return $this->data;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param string $key
     * @param mixed  $value
     * @throws \LogicException
     */
    public function offsetSet($key, $value): void
    {
        throw new \LogicException('Config is immutable');
    }

    /**
     * Unset a configuration option.
     *
     * @param string $key
     * @throws \LogicException
     */
    public function offsetUnset($key): void
    {
        throw new \LogicException('Config is immutable');
    }
}
