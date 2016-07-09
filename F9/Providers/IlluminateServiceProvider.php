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

        //@formatter:off

        $app['composer'] = function ($app) {
            return new Composer($app['filesystem']); };
        $app[IlluminateContainer::class] = function ($app) {
            return $app['illuminate.container']; };
        $app[IlluminateDispatcher::class] = function ($app) {
            return $app['illuminate.events']; };
        $app['files'] = function ($app) {
            return $app['filesystem']; };

        isset($app['illuminate.container']) ?: $app['illuminate.container'] = function () {
            return $this->container; };
        isset($app['illuminate.events']) ?: $app['illuminate.events'] = function ($app) {
            return new IlluminateDispatcher($app['illuminate.container']); };
        isset($app['filesystem']) ?: $app['filesystem'] = function () {
            return new Filesystem(); };

        $this->container->add([Filesystem::class, 'filesystem'], function () use ($app) {
            return $app['filesystem'];});
        $this->container->add([Composer::class, 'composer'], function () use ($app) {
            return $app['composer'];});
        $this->container->add(\Illuminate\Contracts\Container\Container::class, function () use ($app) {
            return $app['illuminate.container'];});

        //@formatter:on
    }
}
