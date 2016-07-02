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
    // these constants trigger the creation of applications
    // with differing contexts. Note also that the value of
    // each is the key into the Config object configuration.
    //
    // ie: config(AppFactory::APP) references the config/app.php
    //     configuration file, etc.
    //

    /** Create an `F9\Application` instance. */
    const API = 'api';

    /** Create an `F9\Api` instance (future). */
    const CLI = 'cli';

    /** Create an `F9\Cli` instance (future). */
    const APP = 'app';

    /** @var bool */
    private static $booted;

    /** @var string - the boot context. ie: 'app' = web app, 'api' = XHR API */
    private static $context;

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
     * @param string $context - the environment context.
     */
    private function __construct($context = self::APP)
    {
        static::$context = $context;
        static::$booted = NULL !== static::$booted ?: FALSE;
        static::$instance = $this;

        $this->install_error_handling();
        $this->detect_environment();
    }

    /**
     * **Returns the Application environment context.**
     *
     * Returns `NULL` if not bootstrapped, otherwise one of:
     *
     *      'app' -- An Application.
     *      'api' -- An Application that receives API requests.
     *      'cli' -- A commandline handler.
     *
     * @return string
     */
    public static function getContext() : string
    {
        return static::$context;
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
        Forge::set(['AppFactory', static::class], $this);
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
        $container['app.context'] = static::APP;

        // we'll start by loading the configuration into the Forge Container
        $container->add([Scope::class, 'context'], function () { return new Scope; });
        $container->add('environment', function () use ($container) { return $container['GlobalScope']; });
        $container->singleton([GlobalScope::class, 'GlobalScope'], $global_scope = new GlobalScope($this));
        $container->singleton([Paths::class, 'paths'], new Paths($paths));
        $container->singleton([Config::class, 'config'], $config = Config::createFromFolder(\CONFIG));

        // the reason we are here
        $app = new NineApplication($container, $config, Events::getInstance());
        $app['app.context'] = $container['app.context'];

        // register the new Application
        $container->singleton(['Application', NineApplication::class], $app);

        // synchronize the Application instance with the forge.
        Forge::setApplication(app());

        // additional $app registrations. @formatter:off
        $app['container']       = $container;
        $app['global.scope']    = $global_scope;
        $app['app.factory']     = $this;
        $app['flashbag']        = $app->factory(function () use ($app) { return $app['session']->getFlashBag(); });
        $app['paths']           = $paths;
        //@formatter:on

        return $app;
    }

}
