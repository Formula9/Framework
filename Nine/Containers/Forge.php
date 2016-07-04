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

use ErrorException;
use F9\Application\Application;
use F9\Exceptions\CannotAddNonexistentClass;
use F9\Exceptions\ContainerConflictError;
use F9\Exceptions\DependencyInstanceNotFound;
use F9\Support\Provider\PimpleDumpProvider;
use Nine\Collections\Config;
use Nine\Exceptions\CollectionExportWriteFailure;
use Nine\Library\Lib;

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
    const VERSION = '0.4.2';

    /** Signal an `add()` to register a singleton. */
    const SINGLETON = TRUE;

    /** Signal an `add()` to register a shared bind. */
    const SHARED = FALSE;

    /** @var Application */
    protected static $app;

    protected function __construct()
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
     * @see `make()` - make (or get) an abstracted class or alias.
     * @see `get()`  - static pseudonym for `make()`.
     * @see `put()`  - static pseudonym for `add()`.
     *
     * @param string|string[] $abstract
     * @param mixed           $concrete
     * @param bool            $shared
     *
     * @throws CannotAddNonexistentClass
     */
    public function add($abstract, $concrete = NULL, $shared = self::SHARED)
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

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * Note: Uses the illuminate/container `call()` method.
     *
     * @param  callable|string $callback
     * @param  array           $args
     *
     * @return mixed
     */
    public function callWithDependencyInjection($callback, array $args = [])
    {
        return $this->call($callback, $args);
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
        $this->add($abstract, $concrete, static::SHARED);
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
    public static function Application() : Application
    {
        return self::$app;
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
     * @param bool $build_catalog
     *
     * @return array
     * @throws CollectionExportWriteFailure
     * @throws DependencyInstanceNotFound
     */
    public static function makePhpStormMeta($build_catalog = FALSE)
    {
        static::$instance ?: new static();

        // conveniences and declarations
        $app = static::$app;
        $self = static::$instance;
        $map = [];
        $code = '';

        // collect illuminate aliases
        $forge_aliases = static::getInstance()->aliases;

        /** @var PimpleDumpProvider $pds */
        $pds = static::find('pimple_dump_service');
        $pds->dump($app);

        // get Pimple keys and merge with aliases
        $keys = $app->keys();

        // fold in forge aliases that do not already exist in the $app.
        foreach ($forge_aliases as $abstract => $alias) {
            // add only what doesn't already exist.
            isset($keys[$abstract]) ?: $keys[] = "\\$abstract";
            // map all cases, however
            $map[] = "'$alias' instanceof \\$abstract,";
        }

        // Iterate through the key list to collect registrations.
        foreach ($keys as $key) {
            // assume nothing
            $appKey = static::key_object_exists($key)
                ? $self->parseValue($app, $key)
                : self::parseKey($app, $key);

            // ignoring 'app' replications, add the new .phpstorm.meta entry.
            if ($appKey and $appKey !== '' and $key !== 'app') {
                $map[] = "'$key' instanceof \\$appKey,";
            }
        }

        // sort and build code segment
        $map = array_unique($map);
        sort($map);

        foreach ($map as $entry) {
            $code .= '            ' . $entry . PHP_EOL;
        }

        $template = <<< TEMPLATE
<?php namespace PHPSTORM_META {

    /**
     * PhpStorm Meta Code-completion index created with Formula 9 Forge.
     *
     * @package Nine
     * @version 0.4.2
     * @author  Greg Truesdell <odd.greg@gmail.com>
     */

    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    /** @noinspection PhpUnusedLocalVariableInspection */
    \$STATIC_METHOD_TYPES = [
        new \F9\Application\Application => ['' == '@',
%%MAP%%
        ],
        path('')      => ['' instanceof \Nine\Collections\Paths,],
        config('')    => ['' instanceof \Nine\Collections\Config,],
        app('')       => ['' instanceof \F9\Application\Application,
%%MAP%%
        ],
        forge('')     => [
            '' instanceof \Nine\Containers\Forge,
%%MAP%%
        ],
        \Nine\Containers\Forge::find('')  => [
%%MAP%%
        ],
        \Nine\Containers\ContainerInterface => [
            '' instanceof \Forge,
%%MAP%%
        ],
    ];
}
TEMPLATE;

        $template = str_replace('%%MAP%%', $code, $template);

        if (FALSE === file_put_contents(ROOT . '.phpstorm.meta.php', $template)) {
            throw new CollectionExportWriteFailure('Unable to update .phpstorm.meta.php.');
        }

        if ($build_catalog) {
            self::build_catalog($keys, $app);
        }

        return $map;
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
     * @see `add()`, `get()`
     *
     * @param string|string[] $abstract
     * @param null            $concrete
     * @param bool            $singleton
     *
     * @return void
     * @throws CannotAddNonexistentClass
     */
    public static function set($abstract, $concrete = NULL, $singleton = FALSE)
    {
        static::$instance = static::$instance ?: new static();
        static::$instance->add($abstract, $concrete, $singleton);
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
     * **Parse an item's type and value.**
     *
     * @param        $container
     * @param string $name
     *
     * @return array|null
     */
    protected function parseValue($container, $name)
    {
        try {
            $element = $container[$name];
        } catch (\Exception $e) {
            return NULL;
        }

        if (is_object($element)) {
            if ($element instanceof \Closure) {
                //$type = 'closure';
                $value = '';
            }
            elseif ($element instanceof Container) {
                //$type = 'class';
                $value = get_class($element); # $this->parseContainer($element);
            }
            else {
                //$type = 'class';
                $value = is_string($element) ? $element : get_class($element);
            }
        }
        elseif (is_array($element)) {
            //$type = 'array';
            $value = '';
        }
        elseif (is_string($element)) {
            //$type = 'string';
            $value = $element;
        }
        elseif (is_int($element)) {
            //$type = 'int';
            $value = $element;
        }
        elseif (is_float($element)) {
            //$type = 'float';
            $value = $element;
        }
        elseif (is_bool($element)) {
            //$type = 'bool';
            $value = $element;
        }
        elseif ($element === NULL) {
            //$type = 'null';
            $value = '';
        }
        else {
            //$type = 'unknown';
            $value = gettype($element);
        }

        return $value;
    }

    /**
     * @param $app
     * @param $key
     *
     * @return Application|null|\Silex\Application|string
     */
    protected static function parseKey($app, $key)
    {
        $appValue = $app[$key];

        switch (gettype($appValue)) {
            case 'object':
                $appKey = get_class($appValue);
                break;
            case 'string':
                $appKey = class_exists($appValue) ? $appValue : NULL;
                break;
            default :
                $appKey = NULL;
                break;
        }

        return $appKey;
    }

    /**
     * @param $keys
     * @param $app
     */
    private static function build_catalog($keys, $app)
    {
        $catalog = [];
        foreach ($keys as $key) {

            $concrete = static::contains($key) ? static::$instance->get($key) : $app[$key];
            $concrete_type = gettype($concrete);

            switch ($concrete_type) {

                case 'object':
                    $class = get_class($concrete);
                    $concrete = $class === 'Closure' ? closure_dump($concrete) : $class;
                    break;

                case 'array':
                    $display = [];
                    if (Lib::is_assoc($concrete)) {
                        foreach ($concrete as $idx => $data) {
                            $display[$idx] = gettype($data) === 'object' ? get_class($data) : $data;
                        }
                    }
                    else {
                        foreach ($concrete as $value) {
                            if (is_array($value)) {
                                if (Lib::is_assoc($value)) {
                                    foreach ($value as $name => $data) {
                                        $display[$key][$name] = gettype($data) === 'object' ? get_class($data) : $data;
                                    }
                                    $concrete = $display;
                                }
                                else {
                                    $display[] = $concrete;
                                } #Lib::is_assoc($value) ? ($concrete) : $concrete;
                            }
                            else {
                                $display[] = gettype($value) === 'object' ? get_class($value) : $value;
                            }
                        }
                    }
                    $concrete = $display;
                    break;

                default :
                    try {
                        $concrete = in_array($concrete_type, ['string', 'boolean', 'integer', 'array'], TRUE) ? $concrete : $concrete_type;

                    } catch (ErrorException $e) {
                        $concrete = $concrete_type;
                    }

                    //return $key;
                    break;
            }

            $catalog[$key] = $concrete;
        }

        $reference = new Config(['catalog' => $catalog]);
        $reference->exportPHPFile(APP, 'catalog');
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private static function key_object_exists($key) : bool
    {
        $parent = parent::getInstance();

        return $parent->bound($key);
    }
}
