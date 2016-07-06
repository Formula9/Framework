<?php namespace Nine\Events;

/*===================================================================================
 = F9 (Formula 9) Personal PHP Framework                                         =
 =                                                                                  =
 = Copyright (c) 2010-2016, Greg Truesdell (<odd.greg@gmail.com>)                   =
 = License: MIT (reference: https://opensource.org/licenses/MIT)                    =
 =                                                                                  =
 = Acknowledgements:                                                                =
 =  - The code provided in this file (and in the Framework in general) may include  =
 = open sourced software licensed for the purpose, refactored code from related     =
 = packages, or snippets/methods found on sites throughout the internet.            =
 =  - All originator copyrights remain in force where applicable, as well as their  =
 =  licenses where obtainable.                                                      =
 ===================================================================================*/

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * **The Nine Events class extends the Symfony EventDispatcher
 * class, and is implemented as a Singleton in the framework.**
 *
 * The Events class manages events throughout the framework, and adds a
 * number of methods to the underlying Dispatcher class.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Events extends EventDispatcher
{
    /** @var Events */
    protected static $_instance;

    /**
     * @param string $event
     * @param Event  $event_object
     *
     * @return \Symfony\Component\EventDispatcher\Event
     */
    public static function dispatchClassEvent(string $event, Event $event_object)
    {
        return static::$_instance->dispatch($event, $event_object);
    }

    /**
     * **Dispatch a generic event.**
     *
     * @param       $event
     * @param array $payload
     * @param bool  $halt
     *
     * @return array|null
     */
    public static function dispatchEvent(string $event, array $payload = [], bool $halt = FALSE)
    {
        static::instantiate();

        return static::$_instance->dispatch($event, new Event($payload, $halt));
    }

    /**
     * **Return an singleton instance of the Class.**
     *
     * If the class has already been instantiated then return the object reference.
     * Otherwise, return a new instantiation.
     *
     * @return Events|static
     */
    static public function getInstance() : Events
    {
        static::instantiate();

        return static::$_instance;
    }

    /**
     * **Instantiates the Singleton if necessary.**
     */
    private static function instantiate()
    {
        static::$_instance = static::$_instance ?: new static();
    }
}
