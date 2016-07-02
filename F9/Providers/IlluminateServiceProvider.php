<?php namespace F9\Support\Provider;

/**
 * @package Formula 9
 * @version 0.3.0
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Illuminate\Events\Dispatcher as IlluminateDispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Nine\Containers\Container as IlluminateContainer;
use Pimple\Container;

class IlluminateServiceProvider extends ServiceProvider
{
    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        $container = $this->container;

        $app['illuminate.container'] = function () { return $this->container; };
        $app['illuminate.events'] = function ($app) { return new IlluminateDispatcher($app['illuminate.container']); };
        $app['filesystem'] = function () { return new Filesystem(); };
        $app['composer'] = function ($app) { return new Composer($app['filesystem']); };

        // bind with the illuminate container (Forge, actually.)
        $this->container->add([Filesystem::class, 'filesystem'], $app['filesystem']);
        $this->container->add([Composer::class, 'composer'], $app['composer']);
        $this->container->singleton([\Illuminate\Contracts\Container\Container::class, 'Container'], $app['illuminate.container']);

        // register with the application for dependency injection
        //$app[Composer::class] = function ($app) { return $app['composer']; };
        $app[IlluminateContainer::class] = function ($app) { return $app['illuminate.container']; };
        $app[IlluminateDispatcher::class] = function ($app) { return $app['illuminate.events']; };
    }
}
