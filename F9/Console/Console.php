<?php namespace F9\Console;

use Illuminate\Filesystem\ClassFinder;
use Nine\Collections\ConfigInterface;
use Nine\Collections\Paths;
use Nine\Library\Lib;
use Symfony\Component\Console\Application;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Console extends Application
{
    /** @var array */
    protected $app_commands = [];

    /** @var ConfigInterface */
    protected $config;

    /** @var array */
    protected $framework_commands = [];

    /** @var Paths */
    protected $paths;

    /**
     * Console constructor.
     *
     * @param ConfigInterface $config
     * @param Paths           $paths
     */
    public function __construct(ConfigInterface $config, Paths $paths)
    {
        $this->config = $config;
        $this->paths = $paths;

        parent::__construct('Formula Nine', \F9\Application\Application::VERSION);

        // in all cases, register the framework commands
        $this->registerFrameworkCommands();
    }

    /**
     * @return array
     */
    public function getAppCommands()
    {
        return $this->app_commands;
    }

    /**
     * @return array
     */
    public function getFrameworkCommands()
    {
        return $this->framework_commands;
    }

    /**
     * Accepts a single-dimension array of Command class names.
     *
     * @param array $command_list
     */
    public function registerAppCommands(array $command_list)
    {
        foreach ($command_list as $command) {
            $this->app_commands[] = Lib::get_class_name($command);
            $this->add(new $command);
        }
    }

    /**
     * Registers any commands found the the provided path.
     *
     * Note: All classes found in the folder are loaded, so don't put anything
     *       but commands there.
     *
     * @param string $command_path
     *
     * @return array
     */
    private function registerCommandsIn(string $command_path) : array
    {
        $commands = [];

        foreach ((new ClassFinder)->findClasses($command_path) as $command) {
            $commands[] = Lib::get_class_name($command);
            $this->add(new $command);
        }

        return $commands;
    }

    /**
     *  Registers the framework commands found in the framework Console\Commands path.
     */
    private function registerFrameworkCommands()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'Commands/';
        $this->framework_commands = $this->registerCommandsIn($path);
    }

}
