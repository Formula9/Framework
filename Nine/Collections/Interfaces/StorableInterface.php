<?php namespace Nine\Collections\Interfaces;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface StorableInterface
{
    /**
     * Put an item in storage by key.
     *
     * @param  string $key
     * @param  mixed $value
     *
     * @return $this
     */
    public function put(string $key, $value);
}
