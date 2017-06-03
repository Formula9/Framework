<?php namespace Application;

use F9\Application\Application;
use F9\Container\Potion;
use Nine\Collections\Config;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class AppLoader
{
    /** @var Config */
    private $config;

    /** @var Potion */
    private $container;

    /** @var array */
    private $definitions;

    /** @var array[string=>bool] $loaded */
    private $loaded;

    public function __construct(Potion $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function load(string $buildClass = Application::class)
    {
        // usually either 'app' or 'api' but may be other things.

        /** @var string $loaderContext */
        $loaderContext = $this->config['app.loader.context'];
        // the basePath to the folder of dependency configurations
        $basePath = $this->config['app.loader.base_path'];

        // collect definitions from the folder
        $this->definitions = Config::createFromFolder($basePath);
        $group = $this->definitions[$loaderContext];

        if ( ! isset($this->loaded[$loaderContext])) {
            $this->loadGroup($group);
            $this->loaded[] = $loaderContext;
        }

        return $this->container[$buildClass];
    }

    /**
     * @return array
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * load a named group of
     *
     * @param $loadGroup
     */
    private function loadGroup($loadGroup)
    {
        $this->container->register($loadGroup);
    }

}
