<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
namespace Nine\Collections;

/**
 * **Scope is a context container.**
 */
interface ScopeInterface
{
    /**
     * **Merge the scope with the provided arrayable items.**
     *
     * @param  mixed $items
     *
     * @return $this
     */
    public function merge($items);

    /**
     * **Register a plugin.**
     *
     * Plugins are stored callable items identifiable by name.
     *
     * @param  string   $name
     * @param  callable $plugin
     *
     * @return void
     */
    public function plugin($name, callable $plugin);

    /**
     * **Get a value from the collection by its dot-notated index.**
     *
     * @param null $query
     * @param null $default
     *
     * @return mixed
     */
    public function get($query, $default = NULL);

    /**
     * **TRUE if an indexed value exists.**
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * **Directly set a key:value pair.**
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function set($key, $value);
}
