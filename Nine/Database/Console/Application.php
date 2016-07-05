<?php

namespace Illuminate\Console;

use Illuminate\Contracts\Console\Application as ApplicationContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Events\ArtisanStarting;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

class Application extends SymfonyApplication implements ApplicationContract
{
    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $laravel;

    /**
     * The output from the previous command.
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $lastOutput;

    /**
     * Create a new Artisan console application.
     *
     * @param  \Illuminate\Contracts\Container\Container $laravel
     * @param  \Illuminate\Contracts\Events\Dispatcher   $events
     * @param  string                                    $version
     */
    public function __construct(Container $laravel, Dispatcher $events, $version)
    {
        parent::__construct('Formula Nine', $version);

        $this->laravel = $laravel;
        $this->setAutoExit(FALSE);
        $this->setCatchExceptions(FALSE);

        $events->fire(new ArtisanStarting($this));
    }

    /**
     * Add a command to the console.
     *
     * @param  \Symfony\Component\Console\Command\Command $command
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    public function add(SymfonyCommand $command)
    {
        if ($command instanceof Command) {
            $command->setLaravel($this->laravel);
        }

        return $this->addToParent($command);
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string $command
     * @param  array  $parameters
     *
     * @return int
     */
    public function call($command, array $parameters = [])
    {
        $parameters = collect($parameters)->prepend($command);

        $this->lastOutput = new BufferedOutput;

        $this->setCatchExceptions(FALSE);

        $result = $this->run(new ArrayInput($parameters->toArray()), $this->lastOutput);

        $this->setCatchExceptions(TRUE);

        return $result;
    }

    /**
     * Get the Laravel application instance.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getLaravel()
    {
        return $this->laravel;
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        return $this->lastOutput ? $this->lastOutput->fetch() : '';
    }

    /**
     * Add a command, resolving through the application.
     *
     * @param  string $command
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    public function resolve($command)
    {
        return $this->add($this->laravel->make($command));
    }

    /**
     * Resolve an array of commands through the application.
     *
     * @param  array|mixed $commands
     *
     * @return $this
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }

    /**
     * Add the command to the parent instance.
     *
     * @param  \Symfony\Component\Console\Command\Command $command
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function addToParent(SymfonyCommand $command)
    {
        return parent::add($command);
    }

    /**
     * Get the default input definitions for the applications.
     *
     * This is used to add the --env option to every available command.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption($this->getEnvironmentOption());

        return $definition;
    }

    /**
     * Get the global environment option for the definition.
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected function getEnvironmentOption()
    {
        $message = 'The environment the command should run under.';

        return new InputOption('--env', NULL, InputOption::VALUE_OPTIONAL, $message);
    }
}
