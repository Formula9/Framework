<?php namespace F9\Support\Provider;

use F9\Support\Services\InjectingControllerResolver;
use Nine\Reflector;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * **This service provider wires a new method for injecting dependencies in
 * controller constructors and methods.**
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class InjectingControllerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['injector'] = $injector = $app->factory(function () { return $this->container->make(Reflector::class); });

        $this->container->add([Reflector::class, 'injector'], function () {
            return new Reflector($this->container, Request::createFromGlobals());
        });

        $app->extend('resolver', function ($resolver, $app) {
            return new InjectingControllerResolver($this->container, $app['injector'], $resolver, $app['callback_resolver']);
        });
    }
}
