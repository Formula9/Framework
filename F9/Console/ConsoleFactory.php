<?php namespace F9\Console;

use F9\Contracts\FactoryInterface;
use F9\Providers\MigrationServiceProvider;
use F9\Providers\SeedServiceProvider;
use F9\Support\Provider\EloquentServiceProvider;
use F9\Support\Provider\IlluminateServiceProvider;
use Illuminate\Events\Dispatcher;
use Nine\Collections\Config;
use Nine\Collections\GlobalScope;
use Nine\Collections\Paths;
use Nine\Collections\Scope;
use Nine\Containers\ContainerInterface;
use Nine\Containers\Forge;
use Nine\Events\Events;
use Silex\Application as SilexApplication;
use Silex\ExceptionHandler;
use Symfony\Component\Debug\ErrorHandler;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
final class ConsoleFactory implements FactoryInterface
{
    /** @var Forge */
    protected static $container;

    /** @var array */
    protected static $env;

    /** @var ConsoleFactory */
    protected static $instance;

    /** @var array $providers - required services for `nine` console use. */
    private static $providers = [
        IlluminateServiceProvider::class,
        EloquentServiceProvider::class,
        MigrationServiceProvider::class,
        SeedServiceProvider::class,
    ];

    private function __construct()
    {
        static::$instance = $this;
        static::$container = Forge::getInstance();

        $this->installErrorHandling();
        $this->detectEnvironment();
    }

    /**
     * **Retrieve the current Application environment.**
     *
     * Default values:
     *
     *      $env = [
     *          'environment' => 'PRODUCTION',
     *          'app_key'     => '[set me]',
     *          'debugging'   => FALSE,
     *          'testing'     => FALSE,
     *      ]
     *
     * @return array
     */
    public static function getEnvironment() : array
    {
        return self::$env;
    }

    /**
     * **Make a new Console Application instance.**
     *
     * @param array $paths
     *
     * @return mixed
     */
    public static function make(array $paths)
    {
        // cache AppFactory instance.
        static::$instance ?: new static($paths);

        // make the application
        return static::$instance->makeConsole($paths);
    }

    /**
     *
     */
    private function detectEnvironment()
    {
        static::$env = [
            'developing' => env('APP_ENV', 'PRODUCTION') !== 'PRODUCTION',
            'app_key'    => env('APP_KEY', '[set me]'),
            'debugging'  => env('DEBUG', FALSE),
            'testing'    => env('TESTING', FALSE),
        ];
    }

    /**
     *
     */
    private function installErrorHandling()
    {
        // register the Symfony error handler
        ErrorHandler::register();

        // activate the internal Silex error handler
        new ExceptionHandler(env('DEBUG', FALSE));
    }

    /**
     * @param array $paths
     *
     * @return Console
     */
    private function makeConsole(array $paths) : Console
    {
        // register and collection common class objects
        list($global_scope, $config, $events) = $this->registerClasses($paths, static::$container);

        // use the Silex\Application class to register providers
        $app = new SilexApplication($config['app']);

        // register instances of the common classes
        $this->registerInstances($config, $app, $events, static::$container, $global_scope);

        // register and boot the common services required by the console
        $this->registerAndBootProviders($app);

        return new Console($config, static::$container->get('Paths'));
    }

    /**
     * @param SilexApplication $app
     */
    private function registerAndBootProviders($app)
    {
        foreach (static::$providers as $provider) {
            $object = new $provider($app);
            $app->register($object);
            ! method_exists($object, 'boot') ?: $object->boot($app);
        }
    }

    /**
     * @param array                    $paths
     * @param ContainerInterface|Forge $container
     *
     * @return array
     */
    private function registerClasses(array $paths, $container)
    {
        // we'll start by loading the configuration into the Forge Container
        $container->add(ContainerInterface::class, function () { return Forge::getInstance(); });
        $container->add([Scope::class, 'context'], function () { return new Scope; });
        $container->add('environment', function () use ($container) { return $container['GlobalScope']; });
        $container->singleton([GlobalScope::class, 'GlobalScope'], $global_scope = new GlobalScope($this));
        $container->singleton([Paths::class, 'Paths'], new Paths($paths));
        $container->singleton([Config::class, 'Config'], $config = Config::createFromFolder(\CONFIG));
        $container->singleton([Events::class, 'Events'], $events = Events::getInstance());
        $container->add('paths', function () use ($container) { return $container['Paths']; });
        $container->add('config', function () use ($container) { return $container['Config']; });

        return [$global_scope, $config, $events];
    }

    /**
     * @param                  $config
     * @param SilexApplication $app
     * @param                  $events
     * @param Forge            $container
     * @param                  $global_scope
     *
     * @return mixed
     */
    private function registerInstances($config, $app, $events, $container, $global_scope)
    {
        $app['config'] = $config;
        $app['nine.events'] = $events;
        $app['illuminate.container'] = $container;
        $container->instance('illuminate.container', $container);
        $container->instance('illuminate.events', new Dispatcher());
        $container->instance('app', $app);

        $container->add('app', function () use ($app) { return $app; });

        // align the Nine Events object with the Core EventDispatcher (Symfony)
        Events::setEventDispatcher($app['dispatcher']);

        // additional $app registrations. @formatter:off
        $app['app.context']     = 'console';
        $app['container']       = $container;
        $app['global.scope']    = $global_scope;
        $app['app.factory']     = $this;
        $app['paths']           = $container['Paths'];
        //@formatter:on

        return $app;
    }
}
