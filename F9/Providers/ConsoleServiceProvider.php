<?php namespace F9\Providers;

use F9\Application\Application;
use F9\Contracts\BootableProvider;
use F9\Support\Provider\ServiceProvider;
use Pimple\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

class ConsoleServiceProvider extends ServiceProvider implements BootableProvider
{

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param Application $app
     */
    public function boot($app)
    {
        // TODO: Implement boot() method.
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        // TODO: Implement register() method.
    }
}
