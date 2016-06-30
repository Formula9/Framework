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

use F9\Application\Application;
use Nine\Collections\Config;
use Nine\Containers\Forge;
use Pimple\Container;
use Pimple\ServiceProviderInterface as PimpleServiceProviderInterface;

abstract class ServiceProvider implements PimpleServiceProviderInterface
{
    /** @var \F9\Application\Application|Container */
    protected $app;

    /** @var Config */
    protected $config;

    /** @var \Nine\Containers\Forge $container */
    protected $container;

    public function __construct(Application $application)
    {
        $this->container = Forge::getInstance();
        $this->app = $application ?: $this->container['app'];
        $this->config = $this->app['config'];
    }

    /**
     * @param Container $app
     */
    public function boot(Container $app) { }

}
