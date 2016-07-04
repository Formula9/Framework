<?php namespace Nine\Containers;

/**
 * **DI is an implementation of a multiplexing or binary container.**
 *
 * This class provides and separates dependency injection and service location
 * by exposing methods to interface with multiple containers.
 *
 * The DI inherits `Illuminate\Container\Container` as a dependency
 * container, and embeds `Pimple\Container` as a service locator and
 * global configuration container.
 *
 * @package Nine Containers
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Illuminate\Database\ConnectionInterface;
use Pimple\Container as PimpleContainer;

class DI extends Container implements ContainerInterface
{
    /** @var ConnectionInterface[] */
    protected static $dependency_containers;

    /** @var bool */
    protected static $fail_with_exception = FALSE;

    /** @var static */
    protected static $instance;

    /** @var ConnectionInterface[] */
    protected static $service_locators;

    /**
     * DI constructor.
     *
     * @param bool $newInstance
     */
    protected function __construct($newInstance = FALSE)
    {
        if ( ! $newInstance or ! static::$instance) {
            // first time around (singleton)
            static::$instance = $this;
            // set illuminate container internal reference (singleton)
            static::setInstance($this);
            // internally reference initial containers in the 'nine' group
            static::$dependency_containers['nine'] = $this;
            static::$service_locators['nine'] = new PimpleContainer;
        }
    }

    /**
     * Add (bind) an abstract to an implementation, with optional alias.
     *
     *  Notes:<br>
     *      - `$abstract` is either `['<abstract>', '<alias>']`, `['<abstract>']` or `'<abstract>'`.<br>
     *      - `$concrete` objects that are not *anonymous functions* are added as **instances**.<br>
     *      - All other cases result in binding.<br>
     *    <br>
     *  *Order is important*.<br>
     *      - Correct: `add([Thing::class, 'thing'], ...)`<br>
     *      - Incorrect: `add(['thing', Thing::class], ...)`<br>
     *    <br>
     *
     * @see `make()` - make (or get) an abstracted class or alias.
     * @see `get()`  - static pseudonym for `make()`.
     * @see `put()`  - static pseudonym for `add()`.
     *
     * @param string|string[] $abstract
     * @param mixed           $concrete
     * @param bool            $shared
     *
     * @throws \InvalidArgumentException
     */
    public function add($abstract, $concrete = NULL, $shared = FALSE)
    {
        static::getInstance();

        // an array, we expect [<class_name>, <alias>]
        if (is_array($abstract)) {

            // validate the abstract
            list($abstract, $alias) = array_values($abstract);

            if ( ! class_exists($abstract)) {
                throw new \InvalidArgumentException(
                    "add(['$abstract', '$alias'],...) makes no sense. `$alias` must refer to an existing class."
                );
            }

            // formatted for illuminate container bind method
            $abstract = [$alias => $abstract];

        }

        // `add` treats non-callable concretes as instances
        if ( ! is_callable($concrete)) {
            $this->instance($abstract, $concrete);

            return;
        }

        $this->bind($abstract, $concrete, $shared);
    }

    /**
     * **Retrieve a concrete from the container.**
     *
     * @param $abstract
     *
     * @return mixed
     *
     * @throws ContainerAbstractNotFound
     */
    public function get($abstract)
    {
        // Note that `get` searches all connected containers.

        // check this container first
        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }

        // check registered containers
        if ($result = static::hasDependency($abstract)) {
            return $result;
        }

        if ($result = static::hasService($abstract)) {
            return $result;
        }

        if (static::$fail_with_exception) {
            throw new ContainerAbstractNotFound($abstract);
        }

        return NULL;
    }

    /**
     * **Report whether an abstract exists in the container.**
     *
     * __Optionally return the concrete if found.__
     *
     * @param string $abstract
     * @param bool   $return_concrete If TRUE, has will return the concrete if the abstract is found.
     *
     * @return bool
     * @see `exists()` - a static pseudonym for `has()`
     */
    public function has($abstract, $return_concrete = TRUE) : bool
    {
        static::getInstance();

        // search this container first
        if ($result = $this->bound($abstract)) {
            return $return_concrete ? $this[$abstract] : FALSE;
        }

        // search added dependency containers
        if ($result = static::hasDependency($abstract, $return_concrete)) {
            return $result;
        }

        // search service locator containers
        if ($result = static::hasService($abstract, $return_concrete)) {
            return $result;
        }

        // default
        return FALSE;
    }

    /**
     * @param string|array $abstract
     * @param mixed        $concrete
     * @param bool         $shared
     */
    public static function addDependency($abstract, $concrete = NULL, $shared = FALSE)
    {
        static::getInstance();

        /** @var static $container */
        static $container;

        // The default is the `nine` $container
        $container = $container ?: static::$dependency_containers['nine'];

        $container->add($abstract, $concrete, $shared);
    }

    /**
     * @param $key
     * @param $dependency_container
     */
    public static function addDependencyContainer($key, $dependency_container)
    {
        static::getInstance();

        if (isset(static::$dependency_containers[$key])) {
            unset(static::$dependency_containers[$key]);
        }

        static::$dependency_containers[$key] = $dependency_container;
    }

    /**
     * **Add a new service to the `nine` service locator container.**
     *
     * @param string $abstract
     * @param mixed  $concrete
     * @param bool   $shared
     */
    public static function addService($abstract, $concrete = NULL, $shared = TRUE)
    {
        static::getInstance();

        /** @var \Pimple\Container $container */
        static $container;

        // The default is the `nine` $container
        $container = $container ?: static::$service_locators['nine'];

        if ($shared) {
            $container[$abstract] = $concrete;

            return;
        }

        $container[$abstract] = $container->factory(function () use ($concrete) { return $concrete; });
    }

    /**
     * **Add a new service locator container to the internal collection.**
     *
     * @param string              $key             Internal name.
     * @param ConnectionInterface $service_locator A service locator or container class.
     */
    public static function addServiceContainer($key, $service_locator)
    {
        static::getInstance();

        if (isset(static::$service_locators[$key])) {
            unset(static::$service_locators[$key]);
        }

        static::$service_locators[$key] = $service_locator;
    }

    /**
     * **A static pseudonym for `has()`.**
     *
     * @param string $abstract
     *
     * @return bool
     */
    public static function exists($abstract) : bool
    {
        return static::$instance->bound($abstract);
    }

    /**
     * **A static pseudonym for `get()`.**
     *
     * @param $abstract
     *
     * @return mixed
     *
     * @throws ContainerAbstractNotFound
     */
    public static function find($abstract)
    {
        return static::$instance->get($abstract);
    }

    /**
     * **Returns a set of matching dependency and service containers matching `$name`.**
     *
     * @param string $name
     *
     * @return array
     */
    public static function getContainerGroup($name = 'nine') : array
    {
        // default is empty
        $containers = ['dependency' => NULL, 'service' => NULL];

        // collect all dependency and service locators
        if ($name === '*') {
            foreach (static::$dependency_containers as $entry => $container) {
                $containers['dependency'][$entry] = $container;
            }

            foreach (static::$service_locators as $entry => $container) {
                $containers['service'][$entry] = $container;
            }

            return $containers;
        }

        // search for matching container names
        if (isset(static::$dependency_containers[$name])) {
            $containers['dependency'] = static::$dependency_containers[$name];
        }

        if (isset(static::$service_locators[$name])) {
            $containers['service'] = static::$service_locators[$name];
        }

        return $containers;
    }

    /**
     * **Get a dependency by searching through the collection of dependency containers.**
     *
     * @param $abstract
     *
     * @return bool|mixed
     *
     * @throws ContainerAbstractNotFound
     */
    public static function getDependency($abstract)
    {
        // find the concrete if it exists
        $concrete = static::hasDependency($abstract);

        // if not reporting with exceptions, then simply return the concrete
        if ( ! static::$fail_with_exception) {
            return $concrete;
        }

        // if search failed the throw an exception
        if ( ! $concrete) {
            throw new ContainerAbstractNotFound($abstract);
        }

        // return a valid concrete
        return $concrete;
    }

    /**
     * **Get the specified dependency container.**
     *
     * @param string $name
     *
     * @return ConnectionInterface[]
     */
    public static function getDependencyContainer($name = 'nine')
    {
        static::getInstance();

        return static::$dependency_containers[$name];
    }

    /**
     * **Return the singleton instance or construct a new container.**
     *
     * @return ContainerInterface|DI|static
     */
    public static function getInstance() : ContainerInterface
    {
        return static::$instance = static::$instance ?: new static;
    }

    /**
     * **Get a service by searching through the collection of service locator containers.**
     *
     * @param string $abstract
     *
     * @return bool|mixed
     *
     * @throws ContainerAbstractNotFound
     */
    public static function getService($abstract)
    {
        // find the concrete if it exists
        $concrete = static::hasService($abstract);

        // if not reporting with exceptions, then simply return the concrete
        if ( ! static::$fail_with_exception) {
            return $concrete;
        }

        // if search failed the throw an exception
        if ( ! $concrete) {
            throw new ContainerAbstractNotFound($abstract);
        }

        // return a valid concrete
        return $concrete;
    }

    /**
     * **Get the specified service container**
     *
     * @param string $name
     *
     * @return ContainerInterface
     */
    public static function getServiceContainer($name = 'nine') : ContainerInterface
    {
        static::getInstance();

        return static::$service_locators[$name];
    }

    /**
     * **Determine if an abstract exists in any of the dependency containers.**
     *
     * @param string $abstract
     * @param bool   $return_concrete
     *
     * @return bool|mixed Returns FALSE if not found else the concrete returned by the abstract.
     */
    public static function hasDependency($abstract, $return_concrete = TRUE) : bool
    {
        // query dependency injection containers first.
        foreach (static::$dependency_containers as $name => $container) {

            // this container and illuminate containers
            if ($container instanceof IlluminateContainer and
                $container->bound($abstract)
            ) {
                /** @var ContainerInterface $container */
                return $return_concrete ? $container->get($abstract) : TRUE;
            }

            // not this container and the container has `has` method
            if ($container !== static::$instance
                and method_exists($container, 'has')
                and $container->has($abstract)
            ) {
                return $return_concrete ? $container[$abstract] : TRUE;
            }
        }

        return FALSE;
    }

    /**
     * **Determine if an abstract exists in any of the service containers.**
     *
     * @param string $abstract
     * @param bool   $return_concrete
     *
     * @return bool|mixed
     */
    public static function hasService(string $abstract, bool $return_concrete = TRUE)
    {
        foreach (static::$service_locators as $service_locator) {
            if ($service_locator instanceof PimpleContainer and isset($service_locator[$abstract])) {
                return $return_concrete ? $service_locator[$abstract] : TRUE;
            }

            // not this container and the container has `has` method
            if ($service_locator !== static::$instance
                and method_exists($service_locator, 'has')
                and $service_locator->has($abstract)
            ) {
                return $return_concrete ? $service_locator[$abstract] : TRUE;
            }
        }

        return FALSE;
    }

    /**
     * **Make a new instance of the container.**
     *
     * @return ContainerInterface|DI|static
     */
    public static function makeContainer() : ContainerInterface
    {
        return new static(TRUE);
    }

    /**
     * **Reset the container**
     *
     * Forgets all internal instances and clears the contents of the container registries.
     */
    public static function reset()
    {
        static::$instance->forgetInstances();

        foreach (static::$dependency_containers as $entry => $dependency_container) {
            unset(static::$dependency_containers[$entry]);
        }

        foreach (static::$service_locators as $entry => $service_locator) {
            unset(static::$service_locators[$entry]);
        }
    }

    /**
     * **Static pseudonym for `add()`.**
     *
     * @param      $abstract
     * @param null $concrete
     * @param bool $shared
     */
    public static function set($abstract, $concrete = NULL, $shared = FALSE)
    {
        static::$instance->add($abstract, $concrete, $shared);
    }

    /**
     * **Enable throwing exceptions on errors and `not found`.**
     *
     * @param bool $enable If TRUE, the container will throw an exception if an abstract is not found.
     */
    public static function useExceptions(bool $enable = TRUE)
    {
        static::$fail_with_exception = $enable;
    }
}
