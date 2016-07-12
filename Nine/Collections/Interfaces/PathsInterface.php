<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
namespace Nine\Collections;

use Nine\Collections\Interfaces\RetrievableInterface;
use Nine\Collections\Interfaces\StorableInterface;

/**
 * **Paths provides a simple interface for handling paths in the F9 framework.**
 */
interface PathsInterface extends StorableInterface, RetrievableInterface
{
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
     * @param string $query
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $query, $default = NULL);

    /**
     * **TRUE if an indexed value exists.**
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key) : bool;
}
