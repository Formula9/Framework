<?php namespace F9\Support\Provider;

/**
 * F9 (Formula 9) Personal PHP Framework
 *
 * Copyright (c) 2010-2016, Greg Truesdell (<odd.greg@gmail.com>)
 * License: MIT (reference: https://opensource.org/licenses/MIT)
 *
 * Acknowledgements:
 *  - The code provided in this file (and in the Framework in general) may include
 * open sourced software licensed for the purpose, refactored code from related
 * packages, or snippets/methods found on sites throughout the internet.
 *  - All originator copyrights remain in force where applicable, as well as their
 *  licenses where obtainable.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Collections\Config;
use Nine\Containers\Forge;
use Pimple\Container;
use Pimple\ServiceProviderInterface as PimpleServiceProviderInterface;

/**
 * The ServiceProvider provides access to the Config, F9\Application (Pimple) and
 * the central dependency container Forge (derived from illuminate/container.)
 *
 * Silex service providers require that the register methods are passed
 * a reference to the Pimple Container.
 *
 */
abstract class ServiceProvider implements PimpleServiceProviderInterface
{
    /** @var \F9\Application\Application|Container */
    protected $app;

    /** @var Config */
    protected $config;

    /** @var \Nine\Containers\Forge $container */
    protected $container;

    /**
     * ServiceProvider constructor.
     *
     * @param \F9\Application\Application|\Silex\Application $application
     */
    public function __construct($application)
    {
        $this->container = Forge::getInstance();
        $this->app = $application ?: $this->container['app'];
        $this->config = $this->app['config'];
    }

}
