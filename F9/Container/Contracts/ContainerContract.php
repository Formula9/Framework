<?php namespace F9\Container\Contracts;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Auryn\ConfigException;
use Auryn\InjectionException;
use Auryn\Injector;
use F9\Container\Exceptions\PotionContainerInvalidContextException;
use F9\Exceptions\CannotAddNonexistentClass;

interface ContainerContract
{
    /**
     * Add (bind) an to an implementation, with optional alias.
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
    public function add($abstract, $concrete = NULL);

    /**
     * @param $original
     * @param $alias
     *
     * @return $this
     * @throws \Auryn\ConfigException
     */
    public function alias($original, $alias);

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string $callback
     * @param  array           $parameters
     * @param  string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = NULL);

    /**
     * Define instantiation directives for the specified class
     *
     * @param string $name The class (or alias) whose constructor arguments we wish to define
     * @param array  $args An array mapping parameter names to values/instructions
     *
     * @return self
     */
    public function define($name, array $args);

    /**
     * Assign a global default value for all parameters named $paramName
     *
     * Global parameter definitions are only used for parameters with no typehint, pre-defined or
     * call-time definition.
     *
     * @param string $paramName The parameter name for which this value applies
     * @param mixed  $value     The value to inject for this parameter name
     *
     * @return self
     */
    public function defineParam($paramName, $value);

    /**
     * @param string $name
     * @param        $callableOrMethodStr
     *
     * @return $this
     * @throws \Auryn\ConfigException
     */
    public function delegate(string $name, $callableOrMethodStr);

    /**
     * @return Injector
     */
    public function getInjector();

    /**
     * **Report whether an exists in the $this or the Application container.**
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function has($abstract) : bool;

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string|array $abstract
     * @param  mixed        $instance
     *
     * @return $this
     */
    public function instance($abstract, $instance);

    /**
     * **Finds an entry of the container by its identifier and returns it.**
     *
     * @param string $abstract Identifier of the entry to look for.
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = []);

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset);

    /**
     * @param string $offset
     *
     * @return Injector
     */
    public function offsetGet($offset);

    /**
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value);

    /**
     * @param string $offset
     *
     * @throws PotionContainerInvalidContextException
     */
    public function offsetUnset($offset);

    /**
     * Parse a configuration array into Potion entries.
     *
     * structure: ['key' => ['share|define|define.param|add' => [$key=>$value|$value],]
     *
     * @param $array
     */
    public function register($array);

    /**
     * Register a prepare callable to modify/prepare objects of type $name after instantiation
     *
     * Any callable or provisionable invokable may be specified. Preparers are passed two
     * arguments: the instantiated object to be mutated and the current Injector instance.
     *
     * @param string $name
     * @param mixed  $callableOrMethodStr Any callable or provisionable invokable method
     *
     * @throws InjectionException if $callableOrMethodStr is not a callable.
     *                            See https://github.com/rdlowrey/auryn#injecting-for-execution
     * @return self
     */
    public function extend($name, $callableOrMethodStr);

    /**
     * Share the specified class/instance across the Injector context
     *
     * @param mixed $nameOrInstance The class or object to share
     *
     * @throws ConfigException if $nameOrInstance is not a string or an object
     * @return self
     */
    public function share($nameOrInstance);

    /**
     * @param array|string $abstract
     * @param null         $concrete
     */
    public function singleton($abstract, $concrete = NULL);
}
