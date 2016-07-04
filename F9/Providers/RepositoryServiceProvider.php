<?php namespace F9\Support\Provider;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Pimple\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

class RepositoryServiceProvider extends ServiceProvider
{

    public function register(Container $app)
    {
        // FileSystem.
        $this->app['FileSystem'] = function () { return new Filesystem(); };

        // Composer.
        $this->app->bind('Composer', function ($app) {
            /** @noinspection PhpParamsInspection */
            return new Composer($app['FileSystem']);
        });

    }
}
