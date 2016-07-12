<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
namespace Nine\Collections\Interfaces;

interface RetrievableInterface
{
    /**
     * Get an item from storage by key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = NULL);

    /**
     * Determine if an item exists by key.
     *
     * @param  mixed $key
     *
     * @return bool
     */
    public function has($key);
}
