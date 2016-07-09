<?php namespace Nine\Database\Console\Seeds;

use Illuminate\Console\Command;
use Nine\Containers\Forge;

abstract class Seeder
{
    /**
     * The console command instance.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * The container instance.
     *
     * @var Forge
     */
    protected $container;

    /**
     * Seed the given connection from the given path.
     *
     * @param  string $class
     *
     * @return void
     */
    public function call($class)
    {
        $this->resolve($class)->run();

        if (isset($this->command)) {
            $this->command->getOutput()->writeln("<info>Seeded:</info> $class");
        }
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Set the console command instance.
     *
     * @param  \Illuminate\Console\Command $command
     *
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  Forge $container
     *
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param  string $class
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        }
        else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }
}
