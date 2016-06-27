<?php namespace F9\Support\Provider;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Application\Application;
use F9\Exceptions\DependencyInstanceNotFound;
use Pimple\Container;
use Tracy\Debugger;

class TracyServiceProvider extends ServiceProvider
{
    /**
     * @param Container $app
     *
     * @return mixed
     *
     * @throws DependencyInstanceNotFound
     */
    public function register(Container $app)
    {
        /** @var Application $app */

        $config = $this->config;

        // this service provider will quietly fail if Tracy is not installed.
        if (class_exists('\Tracy\Debugger') and $config->get('logging.tracy.enabled')) {

            // use the environment to configure the Debugger
            $env = env('APP_ENV') === 'PRODUCTION' ? Debugger::PRODUCTION : Debugger::DEVELOPMENT;

            Debugger::$maxDepth = $config->get('logging.tracy.maxDepth', 6);
            Debugger::enable($env, rtrim($config->get('logging.logPath', LOGS), '/'));
            Debugger::$showLocation = env('DEBUG') and $config->get('logging.tracy.showLocation', FALSE);
            Debugger::$strictMode = $config->get('logging.tracy.strictMode', FALSE);
            Debugger::$showBar = FALSE; # env('DEBUG');

            // use the Tracy Debugger for logging.
            $app['tracy'] = Debugger::getLogger();
            $app['nine.logger'] = function ($app) { return $app['tracy']; };
        }
    }
}
