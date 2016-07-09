<?php namespace F9\Console;

use F9\Contracts\FactoryInterface;
use Nine\Collections\Config;
use Nine\Collections\GlobalScope;
use Nine\Collections\Paths;
use Nine\Collections\Scope;
use Nine\Containers\Forge;
use Nine\Events\Events;
use Pimple\Container;
use Silex\ExceptionHandler;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
final class ConsoleFactory implements FactoryInterface
{
    /** @var array */
    protected static $env;

    /** @var ConsoleFactory */
    protected static $instance;

    private function __construct()
    {
        static::$instance = $this;

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
        $application = static::$instance->makeConsole($paths);

        return $application;
    }

    private function detectEnvironment()
    {
        static::$env = [
            'developing' => env('APP_ENV', 'PRODUCTION') !== 'PRODUCTION',
            'app_key'    => env('APP_KEY', '[set me]'),
            'debugging'  => env('DEBUG', FALSE),
            'testing'    => env('TESTING', FALSE),
        ];
    }

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
        // this is the Illuminate Container
        $container = Forge::getInstance();

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
        $console = new Console($config, $container->get('Paths'));
        $app = new Container($container->getAliases());
        $container->add('app', function () use ($app) { return $app; });
        $app['dispatcher'] = new EventDispatcher();

        // align the Nine Events object with the Core EventDispatcher (Symfony)
        Events::setEventDispatcher($app['dispatcher']);

        // additional $app registrations. @formatter:off
        $app['app.context']     = 'console';
        $app['container']       = $container;
        $app['global.scope']    = $global_scope;
        $app['app.factory']     = $this;
        $app['paths']           = $container['Paths'];
        //@formatter:on

        return $console;
    }
}
