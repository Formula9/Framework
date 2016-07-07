<?php namespace Nine\Containers;

/**
 * @package Nine Containers
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface ContainerInterface
{
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
     * @return
     */
    public function add($abstract, $concrete = NULL);

    /**
     * **Report whether an abstract exists in the container.**
     *
     * @param string $abstract
     *
     * @return bool
     *
     * @see `exists()` - a static pseudonym for `has()`
     */
    public function has($abstract) : bool;

    /**
     * **Retrieve a concrete from the container.**
     *
     * @param $abstract
     *
     * @return mixed
     */
    public function get($abstract);

}
