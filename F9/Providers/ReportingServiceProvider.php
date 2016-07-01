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
 */

use Nine\Logger;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class ReportingServiceProvider extends ServiceProvider
{
    public function boot(Container $app)
    {
        if (env('DEBUG') and isset($app['nine.logger'])) {
            // replace the current logger with the F9 framework logger
            $app['logger'] = new Logger($app['nine.logger']);
        }
    }

    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        ini_set('log_errors', 1);
        strtoupper(env('APP_ENV')) === 'PRODUCTION' ? ini_set('display_errors', 0) : ini_set('display_errors', 1);

        $app['debug'] = env('DEBUG');

        ( ! $app['debug']) ?: $app['dump'] = $app->protect(function ($var) { return (new VarDumper)::dump($var); });

        /** Register the app error factory */
        /** @noinspection PhpUndefinedMethodInspection */
        $app->error(function (\Exception $e) use ($app) {

            // handle HTTP exceptions
            if (get_class($e) === NotFoundHttpException::class) {

                /** @var NotFoundHttpException $e */
                /** @noinspection DegradedSwitchInspection */
                switch ($e->getStatusCode()) {
                    case 404: {
                        return response(view('404.html', ['error' => '404 - Page Not Found.']), 404);
                        break;
                    }
                    default:
                        $message = 'We are sorry, but something went terribly wrong.';
                }

                return new Response($message);
            }

            // not an HTTP exception
            throw $e;
        });

        if ($app['debug']) {

            error_reporting(E_ALL);
            ini_set('display_errors', 1);

            # core debug utilities
            # note that debug requires that the environment has been loaded
            include_once BOOT . 'assets/debug.php';

        }
    }
}
