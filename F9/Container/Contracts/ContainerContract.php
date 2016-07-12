<?php namespace F9\Container\Contracts;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

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
     */
    public function add($abstract, $concrete = NULL);

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string $callback
     * @param  array           $parameters
     *
     * @return mixed
     */
    public function call($callback, array $parameters = []);

    /**
     * **Report whether an exists in the $this or the Application container.**
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function has($abstract) : bool;

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
     * Share the specified class/instance across the Injector context
     *
     * @param mixed $nameOrInstance The class or object to share
     *
     * @return self
     */
    public function share($nameOrInstance);

}
