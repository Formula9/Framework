<?php

/**
 * F9 (Formula Nine) Personal PHP Framework
 *
 * Copyright (c) 2010-2016, Greg Truesdell (<odd.greg@gmail.com>)
 * License: MIT (reference: https://opensource.org/licenses/MIT)
 *
 * Acknowledgements:
 *  - The code provided in this file (and in the Framework in general) may include
 * open sourced software licensed for the purpose, refactored code from related
 * packages, or snippets/methods found on sites throughout the internet.
 *  - All originator copyrights remain in force where applicable, as well as their
 *  licenses where obtainable.
 */

namespace Nine\Containers;

use F9\Application\Application;
use F9\Exceptions\CannotAddNonexistentClass;
use F9\Exceptions\ContainerConflictError;
use F9\Exceptions\DependencyInstanceNotFound;

/**
 * **The Forge is a class repository for dependency injection.**
 *
 * The (F9) Forge is a **Singleton**.
 *
 * Internally, the Forge uses an *Illuminate Container* (the same
 * as used by *Laravel* & *Lumen*) as a class and object repository,
 * and provides the framework with dependency injection and
 * service location.
 *
 * The *Illuminate Container* was chosen for ease of use when
 * implementing *Illuminate Database* and *View* packages required
 * by Eloquent and BladeView respectively. Also, it makes
 * importing (some) Laravel packages considerably easier.
 *
 * *Laravel, Lumen and the Illuminate Packages are the works of
 * Taylor Otwell and more than 100 contributors.*
 *
 * @package Nine Containers
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Forge extends Container implements ContainerInterface
{
    use PhpStormMeta;

    const VERSION = '0.4.2';

    /** Signal an `add()` to register a singleton. */
    const SINGLETON = TRUE;

    /** Signal an `add()` to register a shared bind. */
    const SHARED = FALSE;

    /** @var Application */
    protected static $app;

    public function __construct()
    {
        if ( ! NULL === static::$instance) {
            throw new ContainerConflictError('Cannot continue due to a container instantiation conflict [Forge].');
        }

        static::$app = NULL;
        static::$instance = $this;
        static::setInstance($this);
        static::$instance->add([static::class, 'container'], function () { return $this; });
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
     * @param string|string[] $abstract
     * @param mixed           $concrete
     *
     * @throws CannotAddNonexistentClass
     */
    public function add($abstract, $concrete = NULL)
    {
        $this->register($abstract, $concrete);
    }

    /**
     * **Finds an entry of the container by its identifier and returns it.**
     *
     * @param string $abstract Identifier of the entry to look for.
     * @param array  $parameters
     *
     * @return mixed
     */
    public function get($abstract, array $parameters = [])
    {
        return static::$instance->has($abstract)
            ? static::find($abstract, $parameters)
            : static::$app[$abstract];
    }

    /**
     * Query the container for a list of aliases.
     *
     * @return array - associative array of aliases registered with the container.
     */
    public function getAliases() : array
    {
        return $this->aliases;
    }

    /**
     * Get a list of all registered instances
     *
     * @return array
     */
    public function getInstances() : array
    {
        return $this->instances;
    }

    /**
     * **Report whether an abstract exists in the $this or the Application container.**
     *
     * @param string $abstract
     *
     * @return bool
     *
     * @see `exists()` - a static pseudonym for `has()`
     */
    public function has($abstract) : bool
    {
        // check app first
        return (static::$app and static::$app->offsetExists($abstract)) or $this->bound($abstract);
    }

    /**
     * @param array|string $abstract
     * @param null         $concrete
     */
    public function singleton($abstract, $concrete = NULL)
    {
        $this->register($abstract, $concrete, static::SINGLETON);
    }

    /**
     * **Determine if an abstract has been registered with
     * the container.**
     *
     * Static pseudonym for `has()`.
     *
     * @see `has()`
     *
     * @param  string $abstract
     *
     * @return bool
     *
     * @throws CannotAddNonexistentClass
     */
    public static function contains($abstract) : bool
    {
        static::$instance ?: new static();

        return static::$instance->has($abstract);
    }

    /**
     * Attempt locating an abstract (passing any supplied parameters)
     * from the container and the embedded Application.
     *
     * *Order of events:*
     *      1. Does the abstract exist in the app container?
     *      2. Does the abstract exist in the container?
     *      3. Fail with ContainerAbstractNotFound exception.
     *
     * @param string     $abstract
     * @param array|NULL $parameters
     *
     * @return mixed|null
     *
     * @throws DependencyInstanceNotFound
     */
    public static function find($abstract, array $parameters = [])
    {
        static::$instance = static::$instance ?: new static;

        // find in the app first
        if (static::$app and static::$app->offsetExists($abstract)) {
            return static::$app[$abstract];
        }

        // find in Illuminate/Container
        if (static::$instance->bound($abstract)) {
            return static::$instance->make($abstract, $parameters);
        }

        throw new DependencyInstanceNotFound("Dependency or instance `$abstract` not found.");
    }

    /**
     * Returns the currently referenced `Application` object.
     *
     * @see `Forge::setApplication()`
     *
     * @return Application
     *
     * @throws CannotAddNonexistentClass
     * @throws DependencyInstanceNotFound
     */
    public static function getApplication() : Application
    {
        return self::$app;
    }

    /**
     * Return the current instance, creating a new instance if necessary.
     *
     * This is the 'constructor' for the class.
     *
     * Note that the `static::$instance` property is located in the parent
     * `Illuminate\Container\Container` class.
     *
     * @return Forge|static
     */
    public static function getInstance() : Forge
    {
        return static::$instance = static::$instance ?: new static();
    }

    /**
     * Destroy and rebuild the forge.
     *
     * This is useful mainly for testing.
     *
     * @return Forge
     * @throws CannotAddNonexistentClass
     */
    public static function purge() : Forge
    {
        static::$instance = static::$instance ?: new static();

        // clean and destroy the container
        static::$instance->forgetInstances();

        // destroy this instance reference
        static::$instance = NULL;

        // construct a new instance and return it
        return static::getInstance();
    }

    /**
     * Sets (registers) an abstract definition to the container.
     *
     * Static pseudonym for `add()`.
     *
     * @see      `add()`, `get()`
     *
     * @param string|string[] $abstract
     * @param null            $concrete
     *
     * @throws CannotAddNonexistentClass
     *
     */
    public static function set($abstract, $concrete = NULL)
    {
        static::$instance = static::$instance ?: new static();
        static::$instance->register($abstract, $concrete);
    }

    /**
     * **Assign the Application instance as a reference.**
     *
     * The container uses the reference for seamlessly merging
     * search and retrieval methods to include both the
     * container and the Application.
     *
     * @param Application $app
     */
    public static function setApplication(Application $app)
    {
        // fail if an attempt is made to overwrite
        // an existing Application reference.
        if (static::$app and ($app !== static::$app)) {
            new \RuntimeException(
                'Overwriting an existing Application instance is forbidden.');
        }

        static::$app = $app;
    }

    /**
     * @param          $abstract
     * @param callable $concrete
     * @param bool     $shared
     *
     * @throws CannotAddNonexistentClass
     */
    protected function register($abstract, $concrete = NULL, $shared = self::SHARED)
    {
        // an array, we expect [<class_name>, <alias>]
        if (is_array($abstract)) {

            // validate the abstract
            list($abstract, $alias) = array_values($abstract);

            if ( ! class_exists($abstract)) {
                throw new CannotAddNonexistentClass(
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
}
