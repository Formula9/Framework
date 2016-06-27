<?php namespace F9\Exceptions;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

/**
 * **This exception is thrown when an attempt is made to retrieve a
 * value using an unregistered alias or abstract with the Forge or
 * Container.**
 */
class DependencyInstanceNotFound extends \Exception
{

}
