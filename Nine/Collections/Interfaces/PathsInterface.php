<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
namespace Nine\Collections;

/**
 * **Paths provides a simple interface for handling paths in the F9 framework.**
 */
interface PathsInterface
{
    /**
     * Adds a new path to the collection.
     *
     * @param string $key
     * @param string $path
     *
     * @return static
     * @throws \LogicException
     */
    public function add($key, $path);

    /**
     * @param array $import
     *
     * @return $this|void
     * @throws \LogicException
     */
    public function merge($import);

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
    public function has($key) : bool;
}
