<?php
/*===================================================================================
 = F9 (Formula 9) Personal PHP Framework                                         =
 =                                                                                  =
 = Copyright (c) 2010-2016, Greg Truesdell (<odd.greg@gmail.com>)                   =
 = License: MIT (reference: https://opensource.org/licenses/MIT)                    =
 =                                                                                  =
 = Acknowledgements:                                                                =
 =  - The code provided in this file (and in the Framework in general) may include  =
 = open sourced software licensed for the purpose, refactored code from related     =
 = packages, or snippets/methods found on sites throughout the internet.            =
 =  - All originator copyrights remain in force where applicable, as well as their  =
 =  licenses where obtainable.                                                      =
 ===================================================================================*/

namespace F9\Exceptions;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

class UrlGenerationException extends \Exception
{
    /**
     * Create a new exception for missing route parameters.
     *
     * @param  \Illuminate\Routing\Route $route
     *
     * @return static
     */
    public static function forMissingParameters($route)
    {
        return new static("Missing required parameters for [Route: {$route->getName()}] [URI: {$route->getPath()}].");
    }
}
