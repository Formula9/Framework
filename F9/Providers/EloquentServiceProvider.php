<?php namespace F9\Support\Provider;

/**
 * **Eloquent Service Provider.**
 *
 * _Based heavily on the Illuminate DatabaseServiceProvider class._
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Application\Application;
use F9\Contracts\BootableProvider;
use F9\Events\NineEvents;
use Illuminate\Contracts\Queue\EntityResolver;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\QueueEntityResolver;
use Nine\Events\Events;
use Pimple\Container;

class EloquentServiceProvider extends ServiceProvider implements BootableProvider
{
    /**
     * Bootstrap the application events.
     *
     * @param Application|Container $app
     */
    public function boot(Application $app)
    {
        $app['illuminate.connection.resolver'] = $this->buildConnection($this->config->get('database.connections.default'));
        $this->bootModels($this->buildConnection($this->config->get('database.connections.default')));
        Events::dispatchEvent(NineEvents::ORM_BOOTED, [$app['db']]);
    }

    /**
     * Register the service provider.
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $forge = $this->container;

        $this->registerFactories();

        $app['db.factory'] = function () use ($forge) {
            return $forge['db.factory'];
        };

        $app['db'] = function () use ($forge) {
            return $forge['db'];
        };

        $app['db.connection'] = $app->factory(function () use ($forge) {
            return $forge['db.connection'];
        });
    }

    /**
     * @param array $db_config
     *
     * @return DatabaseManager
     */
    protected function bootIlluminateDatabase(array $db_config)
    {
        $this->bootModels($this->buildConnection($db_config));

        return $this->container['db.manager'];
    }

    /**
     * @param $resolver
     */
    protected function bootModels($resolver)
    {
        Model::clearBootedModels();
        Model::setEventDispatcher($this->app['illuminate.events']);
        Model::setConnectionResolver($resolver);

        Events::dispatchEvent(NineEvents::MODELS_BOOTED);
    }

    /**
     * @param array $db_config
     *
     * @return \Illuminate\Database\ConnectionResolver
     */
    protected function buildConnection(array $db_config)
    {
        $connFactory = new ConnectionFactory($this->container);
        $conn = $connFactory->make($db_config);

        $resolver = new ConnectionResolver();
        $resolver->addConnection('default', $conn);
        $resolver->setDefaultConnection('default');

        return $resolver;
    }

    /**
     *  Register the factories required by illuminate database.
     */
    protected function registerFactories()
    {
        // we need to use the forge di
        $app = $this->container;

        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->container->singleton('db.factory', function () use ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->container->singleton('db.manager', function () use ($app) {
            /** @noinspection PhpParamsInspection */
            return new DatabaseManager($this->container, $app['db.factory']);
        });

        $this->container->bind('db.connection', function () use ($app) {
            return $app['db.manager']->{'connection'}();
        });

        $this->container->singleton('Illuminate\Contracts\Queue\EntityResolver', function () {
            return new QueueEntityResolver;
        });

        $this->container->singleton('db', function ($c) use ($app) {
            return new DatabaseManager($c, $app->get('db.factory'));
        });

    }

    /**
     * Register the queueable entity resolver implementation.
     *
     * @return void
     */
    protected function registerQueueableEntityResolver()
    {
        $this->container->singleton(EntityResolver::class, function () { return new QueueEntityResolver; });
    }
}
