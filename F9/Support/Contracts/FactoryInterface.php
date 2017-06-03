<?php namespace F9\Contracts;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface FactoryInterface
{
    /**
     * **Retrieve the current Application environment.**
     *
     * Default values:
     *
     *      $env = [
     *          'environment' => 'PRODUCTION',
     *          'app_key'     => '[set me]',
     *          'debugging'   => FALSE,
     *          'testing'     => FALSE,
     *      ]
     *
     * @return array
     */
    public static function getEnvironment() : array;

    /**
     * **Make a new Application instance.**
     *
     * @param array $paths
     *
     * @return mixed
     */
    public static function make(array $paths);
}
