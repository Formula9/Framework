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

use App\Events\ApplicationEvent;
use F9\Events\NineEvents;
use F9\Exceptions\ApplicationProviderNotFoundException;
use F9\Exceptions\CannotAddNonexistentClass;
use F9\Support\Contracts\ServiceProviderInterface;
use F9\Support\Provider\ServiceProvider;
use Nine\Collections\Config;
use Nine\Containers\ContainerInterface as Container;
use Nine\Contracts\ConfigInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application\FormTrait;
use Silex\Application\SecurityTrait;
use Silex\Application\TranslationTrait;
use Silex\Application\UrlGeneratorTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use function Nine\elapsed_time_since_request;
use function Nine\throw_if_not;

/**
 * The Application class is the pivotal object for configuring framework providers
 * and events, and initiating route or command handling.
 *
 * The `F9\Application` class may only be created by `AppFactory` _**once**_ after booting.
 * Subsequent attempts will return the already existing `F9\Application` object in its
 * current state.
 *
 * **To instantiate:**
 *
 *      $app = F9\AppFactory::make().
 *
 * **Note:**
 *
 *      The `F9\Application` class inherits from the `Silex\Application` class, which itself
 *      inherits from the `Pimple\Container` class. In addition, the framework employs the
 *      `Forge` container object, which inherits from the `Illuminate\Container` class.
 */
class Application extends \Silex\Application implements Container
{
    const VERSION = '0.4.2';

    /** Add the form() method. Dependency: `FormServiceProvider` */
    use FormTrait;

    /** Add security encodePassword() and isGranted() methods. Dependency: `SecurityServiceProvider` */
    use SecurityTrait;

    /** Add trans() and transChoice() methods. Dependency: `TranslationServiceProvider` */
    use TranslationTrait;

    /** Add path() and url() methods. Dependency: `UrlGeneratorServiceProvider` */
    use UrlGeneratorTrait;

    /** @var static $app This class -- inherits Pimple\Container as application container. */
    protected $app;

    /** @var ConfigInterface $config The framework configuration collection. */
    protected $config;

    /** @var Container $container The framework dependency injection container. */
    protected $container;

    /** @var EventDispatcher $events The application event dispatcher. */
    protected $events;

    /** @var array */
    protected $settings;

    /**
     * **The `F9\Application` object is constructed primarily by the `AppFactory` class.**
     *
     * It is **highly** recommended to avoid directly instantiating this class unless
     * you are clearly aware of the requirements. _That's what the `AppFactory` is for._
     *
     * @param Container              $container
     * @param Config|ConfigInterface $config
     * @param EventDispatcher        $events
     */
    public function __construct(Container $container, Config $config, EventDispatcher $events)
    {
        $this->app = $this;
        $this->config = $config;
        $this->container = $container;
        $this->events = $events;

        // note: app.context is registered by the AppFactory class
        // when instantiating this class, and contains one of either
        // 'app' for an application, 'api' for an API, or 'cli' for
        // a command line.
        //
        // see: AppFactory
        $this->settings = $config[$container->get('app.context')];

        // creates the Silex\Application instance.
        parent::__construct($this->settings);

        $this->configure($config);
    }

    /**
     * **Add (bind) an to an implementation, with optional alias.**
     *
     * This method completes the ContainerInterface requirement and operates
     * solely on the application|pimple container.
     *
     *  Notes:<br>
     *      - `$abstract` is either `['<abstract>', '<alias>']`, `['<abstract>']` or `'<abstract>'`.<br>
     *      - `$concrete` objects that are not *anonymous functions* are added as **instances**.<br>
     *      - All other cases result in binding.<br>
     *    <br>
     *  *Order is important*.<br>
     *      - Correct: `add([Thing::class, 'thing'], ...)`<br>
     *      - Incorrect: `add(['thing', Thing::class], ...)`<br>
     *    <br>
     *
     * @param string|string[] $abstract
     * @param mixed|callable  $concrete
     * @param bool            $shared
     *
     * @throws CannotAddNonexistentClass
     * @throws \InvalidArgumentException
     */
    public function add($abstract, $concrete = NULL, $shared = FALSE)
    {
        // an array, we expect [<class_name>, <alias>]
        if (is_array($abstract)) {

            // validate the abstract
            list($abstract, $alias) = array_values($abstract);

            if ( ! class_exists($abstract)) {
                throw new CannotAddNonexistentClass(
                    "add(['$abstract', '$alias'],...) makes no sense. `$alias` must refer to an existing class."
                );
            }

            $this[$abstract] = $shared
                ? $concrete
                : $this->factory(function () use ($concrete) { return $concrete; });

            return;
        }

        // `add` treats non-callable concretes as instances
        if ( ! is_callable($concrete)) {
            $this[$abstract] = $shared ? $concrete($this) : function () use ($concrete) { return $concrete; };

            return;
        }

        $this[$abstract] = $concrete;
    }

    /**
     * **Boot the `Silex\Application` object and boot all registered providers.**
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->booted = TRUE;

        /**
         * Slow boots:
         *
         * "12|13 -- F9\Support\Provider\RoutingServiceProvider"
         * "24|25 -- F9\Support\Provider\DatabaseServiceProvider"
         * "26|27 -- F9\Support\Provider\EloquentServiceProvider"
         * "34|35 -- Silex\Provider\SecurityServiceProvider"
         */
        foreach ($this->providers as $provider) {
            if ($provider instanceof EventListenerProviderInterface) {
                $provider->subscribe($this, $this['dispatcher']);
            }

            if (method_exists($provider, 'boot')) {
                $provider->boot($this);
            }
        }
    }

    /**
     * **Boot a registered provider, or an array of registered providers, by class name.**
     *
     * **Note:**
     *
     *      If an array of provider class names is provided, and even one
     *      of them is found and booted, then this method returns TRUE. If
     *      any other provider(s) is not registered, then there will be no
     *      indication of which or how many were not found.
     *
     * @param string|array $provider_class The registered provider class(es) to boot (if it exists.)
     *
     * @return bool Returns FALSE
     */
    public function bootProvider($provider_class) : bool
    {
        // assume failure
        $booted = FALSE;

        /** @var ServiceProvider $provider */
        foreach ($this->getProviders() as $provider) {

            if (get_class($provider) === $provider_class and method_exists($provider, 'boot')) {

                $provider->boot($this);

                $booted = TRUE;
            }
        }

        // true if at least one provider was booted/
        return $booted;
    }

    /**
     * This exists solely for template use.
     *
     * @return int
     */
    public function elapsed_time() : int
    {
        return (int) elapsed_time_since_request(TRUE);
    }

    /**
     * **Flash a UI message or clear the flash list.**
     *
     * @param        $message
     * @param string $type
     */
    public function flash($message = NULL, $type = 'info')
    {
        static $session;
        $session = $session ?: $this['session'];

        $message
            ? $session->getFlashBag()->add($type, $message)
            : $session->getFlashBag()->clear();
    }

    /**
     * **Get a list of provider objects registered with the `Silex\Application`.**
     *
     * @return array of registered provider objects.
     */
    public function getProviders() : array
    {
        return $this->providers;
    }

    /**
     * **Get an array of application settings.**
     *
     * **Note:**
     *
     *      Application settings are pulled from the `config/app.php` config file
     *      by `AppFactory::make()`. It is also available as `$app['config']['app']`.
     *
     *      Also note that each setting key is registered individually with the Application.
     *          ie: $app['title'] is the same as `$app['config']['app.title']`.
     *
     * @return array of configuration settings.
     */
    public function getSettings() : array
    {
        return $this->settings;
    }

    /**
     * **Determine if an abstract exists in the app container.**
     *
     * **Note:**
     *
     *      Unlike the `Forge`, which tests for entries in both the `Pimple` and `Illuminate` Containers,
     *      this method tests only for entries in the `Silex\Application` `Pimple` instance.
     *
     *      The purpose of this is to separate the Application container from the support container
     *      when called on the Application instance. ( ie: `app()->has()` or `$app->has()` )
     *
     *      Use static `Forge::contains()` or non-static `forge()->has()` to search for the alias|abstract
     *      keys in both containers.
     *
     * @param string $abstract the abstract or alias.
     *
     * @return bool TRUE if found.
     */
    public function has($abstract) : bool
    {
        return isset($this[$abstract]);
    }

    /**
     * **Run the application with an optional Request.**
     *
     * @param Request|NULL $request
     * @param int          $type
     *
     * @throws \Exception
     */
    public function run(Request $request = NULL, $type = HttpKernelInterface::MASTER_REQUEST)
    {
        if (NULL === $request) {
            $request = Request::createFromGlobals();
        }

        // register the request
        $this['request'] = $request;
        $response = $this->handle($request, $type);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * **Run a specific sub-request through the application.**
     *
     * @param string $uri        the uri to visit
     * @param string $method     default: GET
     * @param array  $parameters optional parameters
     * @param array  $cookies
     * @param array  $files
     * @param array  $server
     * @param null   $content
     */
    public function subRequest($uri, $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = NULL)
    {
        $this->run(Request::create($uri, $method, $parameters, $cookies, $files, $server, $content), HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Configure the application.
     *
     * @param Config|ConfigInterface $config
     */
    protected function configure(Config $config)
    {
        // use the Config object
        $this['config'] = function () use ($config) { return $config; };

        // settings
        $this->boot_settings($this->settings);

        // events
        $this->boot_application_events();

        // configured providers (via config/app.php)
        $this->register_configured_providers();
    }

    /**
     * assign customized application events
     *
     * @throws \InvalidArgumentException
     */
    private function boot_application_events()
    {
        // override Symfony EventDispatcher::class with framework Events::class.
        $this['dispatcher_class'] = get_class($this->events);
    }

    /**
     * Register settings and base objects
     *
     * @param array $settings
     *
     * @throws CannotAddNonexistentClass
     */
    private function boot_settings(array $settings)
    {
        // contextual use for routes and di
        $app = $this;

        // load the local environment
        date_default_timezone_set($this['timezone']);
        mb_internal_encoding($this['mb_internal_encoding']);
        setlocale(LC_ALL, $this['locale']);

        $this['nine.container'] = $this->container; # Forge::getInstance();
        $this['nine.settings'] = function () use ($settings) { return $settings; };
        $this['nine.events'] = function () { return $this->events; };

        // For DI, associate the Pimple\Container with this instance of F9\Application.
        $this->container->add([\Pimple\Container::class, Application::class], function () use ($app) { return $this; });

        // set native dependencies
        $this->container->has('config') ?: $this->container->add([get_class($this->config), 'config'], $this->config);
        $this->container->add([self::class, 'app'], $this);
    }

    /**
     * Register the loaded provider list (from app.php or api.php)
     */
    private function register_configured_providers()
    {
        // load and register all providers listed in config/app.php
        foreach ((array) $this->settings['providers'] as $provider) {
            /** @var ServiceProviderInterface $new_provider */
            //$new_provider = new $provider($this);

            if ( ! class_exists($provider)) {
                throw new ApplicationProviderNotFoundException("Provider '$provider' not found.");
            }

            if (class_exists($provider)) {
                $this->register(new $provider($this));
            }
        }

        $this->boot();

        $this['dispatcher']->dispatch(NineEvents::APPLICATION_STARTUP, new ApplicationEvent($this, $this->settings['providers']));
    }
}
