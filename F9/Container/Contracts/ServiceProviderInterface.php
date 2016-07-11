<?php namespace F9\Container\Contracts;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface ServiceProviderInterface
{
    public function register(ContainerContract $container);
}
