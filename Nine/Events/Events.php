<?php namespace Nine\Events;

use Nine\Contracts\ListenerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    protected static $instance;

    /**
     * @param string $event
     * @param Event  $eventObject
     *
     * @return \Symfony\Component\EventDispatcher\Event
     */
    public static function dispatchClassEvent(string $event, Event $eventObject)
    {
        return static::$instance->dispatch($event, $eventObject);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public static function setEventDispatcher(EventDispatcherInterface $dispatcher)
    {
        static::$instance = $dispatcher;
    }

    /**
     * @return Events
     */
    public static function getDispatcher()
    {
        return static::$instance;
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

        return static::$instance->dispatch($event, new Event($payload, $halt));
    }

    /**
     * Forget a listener.
     *
     * example:
     *
     *      Events::listen('an.event', [$this, 'onAnEvent']);
     *      ...
     *      Events::forget('an.event', [$this, 'onAnEvent']);
     *
     * @param string   $eventName
     * @param callable $listener
     */
    public static function forget(string $eventName, callable $listener)
    {
        static::instantiate();

        static::$instance->removeListener($eventName, $listener);
    }

    /**
     * **Return an singleton instance of the Class.**
     *
     * If the class has already been instantiated then return the object reference.
     * Otherwise, return a new instantiation.
     *
     * @return Events|EventDispatcherInterface|static
     */
    static public function getInstance()
    {
        static::instantiate();

        return static::$instance;
    }

    /**
     * Listen for an event and handle it with an inline callable.
     *
     * example:
     *
     *  `Events::listen(NineEvents::DATABASE_BOOTED, function ($event, $eventName, $dispatcher) { ... }, 100);`
     *
     * @param string   $eventName
     * @param callable $listener
     * @param int      $priority
     */
    public static function listen(string $eventName, callable $listener, int $priority = 0)
    {
        static::instantiate();

        static::$instance->addListener($eventName, $listener, $priority);
    }

    /**
     * Add a ListenerInterface-derived event.
     *
     * example:
     *
     *      Events::subscribe(new DatabaseListener(new DatabaseEvent($app['database']));
     *
     * @param ListenerInterface $subscriber
     */
    public static function subscribe(ListenerInterface $subscriber)
    {
        static::instantiate();

        static::$instance->addSubscriber($subscriber);
    }

    /**
     * Unsubscribe a listener.
     *
     * @param ListenerInterface $subscriber
     */
    public static function unsubscribe(ListenerInterface $subscriber)
    {
        static::instantiate();

        static::$instance->removeSubscriber($subscriber);
    }

    /**
     * **Instantiates the Singleton if necessary.**
     */
    private static function instantiate()
    {
        static::$instance = static::$instance ?: new static();
    }
}
