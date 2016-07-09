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
use F9\Events\EloquentEvent;
use F9\Events\NineEvents;
use Illuminate\Contracts\Queue\EntityResolver;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\QueueEntityResolver;
use Pimple\Container;

class EloquentServiceProvider extends ServiceProvider implements BootableProvider
{
    /**
     * Bootstrap the application events.
     *
     * @param Application|Container $app
     */
    public function boot($app)
    {
        $app['illuminate.connection.resolver'] = $this->buildConnection($this->config->get('database.connections.default'));
        $this->container->add('illuminate.connection.resolver', function () use ($app) {
            return $app['illuminate.connection.resolver']; });

        $this->bootModels($this->buildConnection($this->config->get('database.connections.default')));

        // Eloquent boot event
        $this->app['dispatcher']->dispatch(NineEvents::ORM_BOOTED, new EloquentEvent($this->container->get('db')));
    }

    /**
     * Register the service provider.
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $this->registerFactories();

        $app['db'] = function () { return $this->container['db']; };
        $app['db.connection'] = $app->factory(function () { return $this->container['db.connection']; });
        $app['db.factory'] = function () { return $this->container['db.factory']; };
    }

    /**
     * @param array $dbConfig
     *
     * @return DatabaseManager
     */
    protected function bootIlluminateDatabase(array $dbConfig)
    {
        $this->bootModels($this->buildConnection($dbConfig));

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

        $this->app['dispatcher']->dispatch(NineEvents::MODELS_BOOTED, new EloquentEvent($this->container->get('db')));
    }

    /**
     * @param array $dbConfig
     *
     * @return \Illuminate\Database\ConnectionResolver
     */
    protected function buildConnection(array $dbConfig)
    {
        $connFactory = new ConnectionFactory($this->container);
        $conn = $connFactory->make($dbConfig);

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

        //@formatter:off

        $this->container->singleton('db.factory', function () use ($app) {
            return new ConnectionFactory($app); });

        $this->container->singleton('db.manager', function () use ($app) {
            /** @noinspection PhpParamsInspection */
            return new DatabaseManager($this->container, $app['db.factory']); });

        $this->container->bind('db.connection', function () use ($app) {
            return $app['db.manager']->{'connection'}(); });

        $this->container->singleton('Illuminate\Contracts\Queue\EntityResolver', function () {
            return new QueueEntityResolver; });

        $this->container->singleton('db', function ($c) use ($app) {
            return new DatabaseManager($c, $app->get('db.factory')); });

        //@formatter:on
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
