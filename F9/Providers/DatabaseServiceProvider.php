<?php namespace F9\Support\Provider;

/**
 * @package Nine
 * @version 0.4.2
 * @editor  Greg Truesdell <odd.greg@gmail.com>
 */

use App\Events\DatabaseEvent;
use App\Listener\DatabaseListener;
use F9\Exceptions\CannotAddNonexistentClass;
use F9\Exceptions\DependencyInstanceNotFound;
use Illuminate\Filesystem\ClassFinder;
use Nine\Containers\Forge;
use Nine\Database\Database;
use Pimple\Container;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DatabaseServiceProvider extends ServiceProvider implements EventListenerProviderInterface
{
    /**
     * @param Container $app
     *
     * @throws CannotAddNonexistentClass
     */
    public function boot(Container $app)
    {
        $this->register_databases($app);
        $this->register_models();
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

        $app['database.listener'] = function ($app) {
            return new DatabaseListener(new DatabaseEvent($app['database']));
        };
    }

    public function registerListeners()
    {
        // @TODO
    }

    /**
     * @param Container                $app
     * @param EventDispatcherInterface $dispatcher
     */
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['database.listener']);
    }

    /**
     * Register associated service providers.
     */
    protected function registerServices()
    {
        if ($this->config['database.eloquent_enabled']) {
            $this->app->register(new EloquentServiceProvider($this->app));
        }
    }

    /**
     * @param Container $app
     *
     * @return Container
     */
    private function register_configuration(Container $app)
    {
        $config = $this->config;

        // required if either is used
        if ($this->config['database.eloquent_enabled'] or $this->config['database.database_enabled']) {
            $app['db.connections'] = $config['database.connections'];
        }

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

    /**
     * @param Container $app
     *
     * @return Container
     * @throws CannotAddNonexistentClass
     */
    private function register_databases(Container $app)
    {
        if ($this->config['database.database_enabled']) {

            $config_database = $app['config']['database'];

            // register
            $app['nine.db'] = function () use ($config_database) { return new Database($config_database); };
            $app['database'] = function ($app) { return $app['nine.db']; };

            /** @noinspection PhpUndefinedMethodInspection */
            $pdo = $app['database']->getPDO();

            $app['pdo'] = $pdo;
            Forge::set([\PDO::class, 'pdo'], $pdo);
            Forge::set([Database::class, 'database'], function () use ($app) {
                return $app['database'];
            });
        }
    }

    /**
     * Register all of the model classes found in the db/model folder.
     *
     * @throws CannotAddNonexistentClass
     */
    private function register_models()
    {
        // if using eloquent then register all of the eloquent models.
        if ($this->config['database.eloquent_enabled']) {
            // register all of the model classes
            foreach ((new ClassFinder)->findClasses(path('database') . 'models') as $model) {
                Forge::set($model, function () use ($model) { return new $model; });
            }
        }
    }
}
