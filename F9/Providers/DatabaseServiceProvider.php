<?php namespace F9\Support\Provider;

/**
 * @package Nine
 * @version 0.4.2
 * @editor  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Application\Application;
use F9\Contracts\BootableProvider;
use F9\Events\DatabaseEvent;
use F9\Events\NineEvents;
use F9\Exceptions\CannotAddNonexistentClass;
use F9\Exceptions\DependencyInstanceNotFound;
use Illuminate\Filesystem\ClassFinder;
use Nine\Containers\Forge;
use Nine\Database\Connections;
use Nine\Database\Database;
use Nine\Database\NineBase;
use Pimple\Container;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DatabaseServiceProvider extends ServiceProvider implements BootableProvider, EventListenerProviderInterface
{
    /**
     * @param Application|Container $app
     */
    public function boot($app)
    {
        $this->registerDatabases($app);
        $this->registerModels();
    }

    /**
     * Register Database-related configurations and dependencies/
     *
     * @param Container $app
     *
     * @throws CannotAddNonexistentClass
     * @throws DependencyInstanceNotFound
     */
    public function register(Container $app)
    {
        $this->register_configuration($app);
        $this->registerServices();

        //$app['database.listener'] = function ($app) {
        //    return new DatabaseListener(new DatabaseEvent($app['database']));
        //};
    }

    public function registerListeners()
    {
        //
    }

    /**
     * @param Container                $app
     * @param EventDispatcherInterface $dispatcher
     */
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        if (isset($app['database.listener'])) {
            $dispatcher->addSubscriber($app['database.listener']);
        }
    }

    /**
     * Register associated service providers.
     */
    protected function registerServices()
    {
        // local copies
        list($app, $config, $container) = [$this->app, $this->config, $this->container];

        // Nine Database and NineBase
        if ($config['database.database_enabled']) {
            // NineBase
            $this->container->add([NineBase::class, 'NineBase'],
                function () use ($container) {
                    /** @var Forge $container */
                    return new NineBase($container->get('Connections'));
                }
            );

            // Nine\Database
            $this->container->add([Database::class, 'Database'],
                function () use ($config) { return new Database($config['database']); });
        }

        if ($config['database.eloquent_enabled']) {
            $this->app->register(new EloquentServiceProvider($this->app));
        }
    }

    /**
     * @param Container $app
     *
     * @return Container
     * @throws CannotAddNonexistentClass
     */
    private function registerDatabases(Container $app)
    {
        if ($this->config['database.database_enabled']) {

            $config_database = $app['config']['database'];

            // register
            $app['nine.db'] = function () use ($config_database) { return new Database($config_database); };
            $app['ninebase'] = function () use ($config_database) { return new NineBase(new Connections($config_database)); };
            $app['database'] = function ($app) { return $app['nine.db']; };

            Forge::set([Database::class, 'database'], function () use ($app) { return $app['database']; });
            $this->app['dispatcher']->dispatch(NineEvents::DATABASE_BOOTED, new DatabaseEvent($app['nine.db']));
        }
    }

    /**
     * Register all of the model classes found in the db/model folder.
     *
     * @throws CannotAddNonexistentClass
     */
    private function registerModels()
    {
        // if using eloquent then register all of the eloquent models.
        if ($this->config['database.eloquent_enabled']) {
            // register all of the model classes
            foreach ((new ClassFinder)->findClasses(path('database') . 'models') as $model) {
                Forge::set($model, function () use ($model) { return new $model; });
            }
        }
    }

    /**
     * @param Container $app
     */
    private function register_configuration(Container $app)
    {
        // local copies
        list($config, $container) = [$this->config, $this->container];

        // Common database configurations
        if ($this->config['database.eloquent_enabled'] or $this->config['database.database_enabled']) {

            // build the database connection collection
            $this->container->singleton([Connections::class, 'Connections'],
                function () use ($config) { return new Connections($config['database']); });

            //$this->container['database.connections'] = new Connections($config['database']);
            $app['database.connections'] = function () use ($container) { return $container['Connections']; };

        }

        // Illuminate Database & Eloquent
        if ($this->config['database.eloquent_enabled']) {
            // configuration
            // inject illuminate components
            $app['db.container'] = function ($app) { return $app['illuminate.container']; };
            $app['db.dispatcher'] = function ($app) { return $app['illuminate.events']; };

            $app['db.global'] = $config['database.eloquent_global'];
            $app['db.eloquent_enabled'] = $config['database.eloquent_enabled'];
            $app['db.logging'] = $config['database.logging'];
        }
    }
}
