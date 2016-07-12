<?php namespace Nine\Traits;

/**
 * A Trait that completes a class that implements \ArrayAccess by
 * adding required and additional methods for accessing the underlying
 * container.
 *
 * @note    : The containing property must be `$items`.
 *
 * @package Nine Traits
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Library\Lib;

/**
 * WithItemArrayAccess expects that an $items property exists. It cannot operate without it.
 *
 * @property array $items Reference to $items property for hinting.
 */
trait WithItemArrayAccess
{
    /**
     * **Dynamically retrieve the value of an attribute.**
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * **Dynamically set the value of an attribute.**
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * **Append a new key::value to the item array.**
     *
     * Note: if $value is not a key::value then it is converted to an array.
     *
     * @param string        $key - $key may be a simple string or a dot notation string.
     * @param array | mixed $value
     *
     * @return array|mixed|null
     */
    public function append($key, $value = NULL)
    {
        if ( ! $this->has($key)) {
            $this->put($key, $value);

            return $value;
        }
        else {
            throw new \InvalidArgumentException("Cannot append an already existing key: '$key'");
        }
    }

    /**
     * **Return a copy of the collection contents.**
     *
     * @return array
     */
    public function copy() : array
    {
        $copy = [];

        return array_merge_recursive($copy, $this->items);
    }

    /**
     * **Get a value from the collection by its dot-notated index.**
     *
     * @param string $query
     * @param null   $default
     *
     * @return mixed
     */
    public function get(string $query, $default = NULL)
    {
        return Lib::array_query($this->items, $query, value($default));
    }

    /**
     * **TRUE if an indexed value exists.**
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key) : bool
    {
        return NULL !== Lib::array_query($this->items, $key);
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->put($offset, $value);
    }

    /**
     * @param mixed|string $offset
     */
    public function offsetUnset($offset)
    {
        Lib::array_forget($this->items, $offset);
    }

    /**
     * **Locate a value by `$key`, return `$default` if not found.**
     *
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    public function search_and_replace($key, $default = NULL)
    {
        return Lib::array_search_and_replace($this->items, $key, $default);
    }

    /**
     * **Directly set a key:value pair.**
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function put(string $key, $value)
    {
        # attempt writing the value to the key
        if (is_string($key)) {
            list($key, $value) = Lib::expand_segments($key, $value);
            $this->search_and_replace([$key => $value]);
        }
    }

    /**
     * **Returns an array of items from Collection or Arrayable.**
     *
     * @param  mixed $items
     *
     * @return array
     */
    protected function getArrayableItems($items) : array
    {
        if ($items instanceof self) {
            return $items->copy();
        }

        if (is_object($items) && method_exists($items, 'toArray')) {
            return $items->toArray();
        }

        if (is_object($items) && method_exists($items, 'toJson')) {
            return json_decode($items->toJson(), TRUE);
        }

        return (array) $items;
    }
}
