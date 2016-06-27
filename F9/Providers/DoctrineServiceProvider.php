<?php namespace F9\Support\Provider;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Pimple\Container as Pimple;
use Pimple\Container;
use Silex\Application;

/**
 * Doctrine DBAL Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoctrineServiceProvider extends ServiceProvider
{
    /**
     * @param Container $app
     *
     * @throws DBALException
     * @throws \InvalidArgumentException
     */
    public function register(Container $app)
    {
        $app['db.default_options'] = [
            'driver'   => 'pdo_mysql',
            'dbname'   => env('DB_DATABASE', NULL),
            'host'     => env('DB_HOST', 'localhost'),
            'user'     => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', NULL),
        ];

        $app['dbs.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = FALSE;

            if ($initialized) {
                return;
            }

            $initialized = TRUE;

            if ( ! isset($app['dbs.options'])) {
                $app['dbs.options'] = ['default' => isset($app['db.options']) ? $app['db.options'] : []];
            }

            $tmp = $app['dbs.options'];

            /** @noinspection AlterInForeachInspection */
            foreach ($tmp as $name => &$options) {
                /** @noinspection ReferenceMismatchInspection */
                $options = array_replace($app['db.default_options'], $options);

                if ( ! isset($app['dbs.default'])) {
                    $app['dbs.default'] = $name;
                }
            }
            $app['dbs.options'] = $tmp;
        });

        $app['dbs'] = function ($app) {
            $app['dbs.options.initializer']();

            $dbs = new Pimple;
            foreach ($app['dbs.options'] as $name => $options) {
                if ($app['dbs.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $app['db.config'];
                    $manager = $app['db.event_manager'];
                }
                else {
                    $config = $app['dbs.config'][$name];
                    $manager = $app['dbs.event_manager'][$name];
                }

                $dbs[$name] = function () use ($options, $config, $manager) {
                    return DriverManager::getConnection($options, $config, $manager);
                };
            }

            return $dbs;
        };

        $app['dbs.config'] = function ($app) {
            $app['dbs.options.initializer']();

            $configs = new Pimple();
            foreach ($app['dbs.options'] as $name => $options) {
                $configs[$name] = new Configuration();

                if (isset($app['logger']) && class_exists('Symfony\Bridge\Doctrine\Logger\DbalLogger')) {
                    $configs[$name]->setSQLLogger(new DbalLogger($app['logger'], isset($app['stopwatch']) ? $app['stopwatch'] : NULL));
                }
            }

            return $configs;
        };

        $app['dbs.event_manager'] = function ($app) {
            $app['dbs.options.initializer']();

            $managers = new Pimple();
            foreach ($app['dbs.options'] as $name => $options) {
                $managers[$name] = new EventManager();
            }

            return $managers;
        };

        // shortcuts for the "first" DB
        $app['dbx'] = function ($app) {
            $dbs = $app['dbs'];

            /** @noinspection PhpIllegalArrayKeyTypeInspection */
            return $dbs[$app['dbs.default']];
        };

        $app['db.config'] = function ($app) {
            $dbs = $app['dbs.config'];

            /** @noinspection PhpIllegalArrayKeyTypeInspection */
            return $dbs[$app['dbs.default']];
        };

        $app['db.event_manager'] = function ($app) {
            $dbs = $app['dbs.event_manager'];

            /** @noinspection PhpIllegalArrayKeyTypeInspection */
            return $dbs[$app['dbs.default']];
        };
    }
}
