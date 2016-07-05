<?php namespace F9\Console;

use F9\Providers\MigrationServiceProvider;
use Illuminate\Console\Application;
use Illuminate\Filesystem\ClassFinder;
use Nine\Collections\ConfigInterface;
use Nine\Collections\Paths;
use Nine\Library\Lib;

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
     * @param string          $version
     */
    public function __construct(ConfigInterface $config, Paths $paths, $version = \F9\Application\Application::VERSION)
    {
        $this->config = $config;
        $this->paths = $paths;

        parent::__construct(forge('illuminate.container'), forge('illuminate.events'), $version);

        (new MigrationServiceProvider(forge('app')))->register(forge('app'));

        // in all cases, register the framework commands
        $this->registerFrameworkCommands();

        $this->registerArtisanCommands();
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
     * Registers an array of application mode Command class names.
     *
     * Reads all classes located in the given path, so only Commands
     * should be the only classes in the path.
     *
     * @param string $command_path
     */
    public function registerAppCommandsIn(string $command_path)
    {
        $commands = $this->registerCommandsIn($command_path);

        // this may not be the only call to register application mode commands,
        // so merge with the app commands array.
        $this->app_commands = array_merge($this->app_commands, $commands);
    }

    /**
     *  Register the Artisan migration commands.
     */
    private function registerArtisanCommands()
    {
        $this->add(app('command.migrate'));
        $this->add(app('command.migrate.install'));
        $this->add(app('command.migrate.make'));
        $this->add(app('command.migrate.refresh'));
        $this->add(app('command.migrate.reset'));
        $this->add(app('command.migrate.rollback'));
        $this->add(app('command.migrate.status'));
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
