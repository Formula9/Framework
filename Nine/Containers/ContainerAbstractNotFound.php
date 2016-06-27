<?php namespace Nine\Containers;

/**
 * @package Nine Containers
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

class ContainerAbstractNotFound extends \Exception
{
    public function __construct($abstract, $message = NULL)
    {
        parent::__construct($message ?: "Abstract $abstract not found in any container.");
    }
}
