<?php namespace F9\Support\Provider;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use App\Provider\ControllerRoutingProvider;
use F9\Application\Application;
use F9\Contracts\BootableProvider;
use Pimple\Container;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class RoutingServiceProvider extends ServiceProvider implements BootableProvider
{

    public function boot($app)
    {
        /** @var \Silex\Application $app */
        if (class_exists(ControllerRoutingProvider::class)) {
            $app->mount('', new ControllerRoutingProvider($app));
        }
    }

    public function register(Container $app)
    {
        include_once APP . 'routes.php';

        $app->register(new InjectingControllerServiceProvider($this->app));
        $app->register(new ServiceControllerServiceProvider);

        $app[Request::class] = $app->factory(function () { return Request::createFromGlobals(); });
        $app['request'] = $app->factory(function ($app) { return $app[Request::class]; });

        $this->container->add(Request::class, function () use ($app) { return $app[Request::class]; });

        $app->register(new HttpCacheServiceProvider, [
            'http_cache.cache_dir' => $app['config']['core.http_cache_dir'],
        ]);

        $app->register(new HttpFragmentServiceProvider);
        $app->register(new UrlGeneratorServiceProvider);
    }
}
