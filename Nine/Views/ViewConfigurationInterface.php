<?php namespace Nine\Views;

use Nine\Collections\Scope;
use Nine\Events\Events;

/**
 * @package Nine
 * @version 0.4.0
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
interface ViewConfigurationInterface
{
    /**
     * @return Events
     */
    public function events();

    /**
     * @return Scope
     */
    public function globalScope() : Scope;

    /**
     * @return array
     */
    public function paths() : array;

    /**
     * @return array
     */
    public function settings() : array;

}
