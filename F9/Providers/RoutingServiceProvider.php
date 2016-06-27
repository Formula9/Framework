<?php namespace F9\Support\Provider;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use App\Provider\ControllerRoutingProvider;
use F9\Application\Application;
use F9\Exceptions\CannotAddNonexistentClass;
use InvalidArgumentException;
use Pimple\Container;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class RoutingServiceProvider extends ServiceProvider
{

    public function boot(Container $app)
    {
        /** @var \Silex\Application $app */
        $app->mount('', new ControllerRoutingProvider());
    }

    /**
     * @param Container|Application $app
     *
     * @throws InvalidArgumentException
     * @throws CannotAddNonexistentClass
     * @throws \LogicException
     */
    public function register(Container $app)
    {
        include_once APP . 'routes.php';

        $app->register(new InjectingControllerServiceProvider($app));
        $app->register(new ServiceControllerServiceProvider);

        $app[Request::class] = $app->factory(function () { return Request::createFromGlobals(); });
        $app['request'] = $app->factory(function ($app) { return $app[Request::class]; });

        $app['nine.container']->add(Request::class, function () use ($app) { return $app[Request::class]; });

        $app->register(new HttpCacheServiceProvider, [
            'http_cache.cache_dir' => $app['config']['core.http_cache_dir'],
        ]);

        $app->register(new HttpFragmentServiceProvider);
        $app->register(new UrlGeneratorServiceProvider);
    }
}
