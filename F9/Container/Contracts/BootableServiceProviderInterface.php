<?php namespace F9\Container\Contracts;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface BootableServiceProviderInterface
{
    public function boot(ContainerContract $container);
}
