<?php namespace F9\Application;

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
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Application\Application as NineApplication;
use F9\Exceptions\ConfigurationException;
use F9\Exceptions\FeatureNotImplemented;
use Nine\Collections\Config;
use Nine\Collections\GlobalScope;
use Nine\Collections\Paths;
use Nine\Collections\Scope;
use Nine\Containers\Forge;
use Nine\Events\Events;
use Silex\Application as SilexApplication;
use Silex\ExceptionHandler;
use Symfony\Component\Debug\ErrorHandler;

/**
 * __The `AppFactory` class boots the framework environment and constructs the
 * `F9\Application object.`__
 *
 * The `F9\Application` class inherits from the `Silex\Application` class, which itself
 * inherits from the `Pimple\Container` class. In addition, the framework employs the
 * `F9` container object, which inherits from the `Illuminate\Container` class.
 *
 * `AppFactory` ensures that the dual containers are configured properly for the
 * Application. In future versions, `AppFactory` will also create API Applications
 * as well as CLI applications.
 *
 * See: `F9\Application`, `Silex\Application`, `Forge`, `Pimple\Container`
 */
class AppFactory
{
    /** @var bool */
    private static $booted;

    /** @var array - booted environment */
    private static $env = [
        'environment' => 'PRODUCTION',
        'app_key'     => '[set me]',
        'debugging'   => FALSE,
        'testing'     => FALSE,
    ];

    /** @var AppFactory */
    private static $instance;

    /**
     * Given a context value ('app','api','cli'), the `AppFactory::__construct`
     * method builds an execution environment based on the needs of the
     * Application context.
     *
     * This is a `private` method called by the static `make_*` methods.
     *
     */
    private function __construct()
    {
        //static::$context = $context;
        static::$booted = NULL !== static::$booted ?: FALSE;
        static::$instance = $this;

        $this->install_error_handling();
        $this->detect_environment();
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
     * **Make a new Application instance.**
     *
     * @param array $paths
     *
     * @return Application
     */
    public static function make(array $paths) : Application
    {
        // The Application is, in effect, a singleton...
        // so if the application already exists then simply return it.
        if ((NULL !== static::$instance) and (NULL !== Forge::Application())) {
            return Forge::Application();
        }

        // cache AppFactory instance.
        static::$instance ?: new static($paths);

        // block anything else from making a new Application.
        static::$booted = TRUE;

        // make the application
        $application = static::$instance->make_application($paths);
        Forge::setApplication($application);

        return $application;
    }

    /**
     * **Make an API Application.** (future)
     *
     * @return Application
     * @throws FeatureNotImplemented
     */
    public static function makeAPI()
    {
        throw new FeatureNotImplemented();
    }

    /**
     * **Detect and configure the environment.**
     *
     * @throws ConfigurationException
     */
    private function detect_environment()
    {
        static::$env = [
            'developing' => env('APP_ENV', 'PRODUCTION') !== 'PRODUCTION',
            'app_key'    => env('APP_KEY', '[set me]'),
            'debugging'  => env('DEBUG', FALSE),
            'testing'    => env('TESTING', FALSE),
        ];

        // register this factory
        Forge::set([static::class, 'AppFactory'], $this);
    }

    /**
     * **Install Error and Exception handlers.**
     */
    private function install_error_handling()
    {
        // register the Symfony error handler
        ErrorHandler::register();
        // activate the internal Silex error handler
        new ExceptionHandler(env('DEBUG'));
    }

    /**
     * @param array $paths
     *
     * @return Application
     */
    private function make_application(array $paths) : Application
    {
        // this is the Illuminate Container
        $container = Forge::getInstance();
        // running as a web app
        $container['app.context'] = 'app';

        // we'll start by loading the configuration into the Forge Container
        $container->add([Scope::class, 'context'], function () { return new Scope; });
        $container->add('environment', function () use ($container) { return $container['GlobalScope']; });
        $container->singleton([GlobalScope::class, 'GlobalScope'], $global_scope = new GlobalScope($this));
        $container->singleton([Paths::class, 'Paths'], new Paths($paths));
        $container->singleton([Config::class, 'Config'], $config = Config::createFromFolder(\CONFIG));
        $container->singleton([Events::class, 'Events'], $events = Events::getInstance());

        $container->add('paths', function () use ($container) { return $container['Paths']; });
        $container->add('config', function () use ($container) { return $container['Config']; });

        // the reason we are here
        $app = new NineApplication($container, $config, $events, $global_scope);
        $app['app.context'] = $container['app.context'];

        // register the new Application
        $container->singleton([NineApplication::class, 'Application'], $app);

        // synchronize the Application instance with the forge.
        Forge::setApplication($app);

        // additional $app registrations. @formatter:off
        $app['container']       = $container;
        $app['global.scope']    = $global_scope;
        $app['app.factory']     = $this;
        $app['flashbag']        = $app->factory(function () use ($app) { return $app['session']->getFlashBag(); });
        $app['paths']           = $container['Paths'];
        //@formatter:on

        return $app;
    }

}
