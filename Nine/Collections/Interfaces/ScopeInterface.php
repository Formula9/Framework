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
 * **Scope is a context container.**
 */
interface ScopeInterface extends StorableInterface, RetrievableInterface
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

}
