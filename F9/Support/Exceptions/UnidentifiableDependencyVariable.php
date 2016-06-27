<?php namespace F9\Exceptions;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

/**
 * **This exception is thrown by the router when attempting to
 * dispatch a route using dependency injection, and a parameter
 * (variable) is not identifiable, and therefore cannot be
 * instantiated.**
 */
class UnidentifiableDependencyVariable extends \Exception
{

}
