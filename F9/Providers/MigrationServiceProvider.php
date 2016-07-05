<?php namespace F9\Providers;

use F9\Support\Provider\ServiceProvider;
use Nine\Console\Events\ArtisanStarting;
use Nine\Database\Console\Migrations\InstallCommand;
use Nine\Database\Console\Migrations\MigrateCommand;
use Nine\Database\Console\Migrations\MigrateMakeCommand;
use Nine\Database\Console\Migrations\RefreshCommand;
use Nine\Database\Console\Migrations\ResetCommand;
use Nine\Database\Console\Migrations\RollbackCommand;
use Nine\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Pimple\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class MigrationServiceProvider extends ServiceProvider
{

    public function register(Container $pimple)
    {
        $this->registerRepository();

        // Once we have registered the migrator instance we will go ahead and register
        // all of the migration related commands that are used by the "Artisan" CLI
        // so that they may be easily accessed for registering with the consoles.
        $this->registerMigrator();

        $this->registerCreator();

        $this->registerCommands();
    }

    /**
     * Register the package's custom Artisan commands.
     *
     * @param  array|mixed $commands
     *
     * @return void
     */
    protected function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        // To register the commands with Artisan, we will grab each of the arguments
        // passed into the method and listen for Artisan "start" event which will
        // give us the Artisan console instance which we will give commands to.
        $events = $this->container->get('illuminate.events');

        $events->listen(ArtisanStarting::class, function ($event) use ($commands) {
            $event->artisan->resolveCommands($commands);
        });
    }

    /**
     * Register all of the migration commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $commands = ['Migrate', 'Rollback', 'Reset', 'Refresh', 'Install', 'Make', 'Status'];

        // We'll simply spin through the list of commands that are migration related
        // and register each one of them with an application container. They will
        // be resolved in the Artisan start file and registered on the console.
        foreach ($commands as $command) {
            $this->{'register' . $command . 'Command'}();
        }

        // Once the commands are registered in the application IoC container we will
        // register them with the Artisan start event so that these are available
        // when the Artisan application actually starts up and is getting used.
        $this->commands(
            'command.migrate', 'command.migrate.make',
            'command.migrate.install', 'command.migrate.rollback',
            'command.migrate.reset', 'command.migrate.refresh',
            'command.migrate.status'
        );
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->container->singleton('migration.creator', function () {
            return new MigrationCreator($this->app['files']);
        });

        $this->app['migration.creator'] = function () { return $this->container['migration.creator']; };
    }

    /**
     * Register the "install" migration command.
     *
     * @return void
     */
    protected function registerInstallCommand()
    {
        $this->container->singleton('command.migrate.install', function () {
            return new InstallCommand($this->container->get('migration.repository'));
        });

        $this->app['command.migrate.install'] = function () { return $this->container['command.migrate.install']; };
    }

    /**
     * Register the "make" migration command.
     *
     * @return void
     */
    protected function registerMakeCommand()
    {
        $this->container->singleton('command.migrate.make', function () {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $this->container->get('migration.creator');

            $composer = $this->container->get('composer');

            return new MigrateMakeCommand($creator, $composer);
        });

        $this->app['command.migrate.make'] = function () { return $this->container['command.migrate.make']; };
    }

    /**
     * Register the "migrate" migration command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->container->singleton('command.migrate', function () {
            return new MigrateCommand($this->container->get('migrator'));
        });

        $this->app['command.migrate'] = function () { return $this->container['command.migrate']; };
    }

    /**
     * Register the migrator service.
     *
     * @return void
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        $this->container->singleton('migrator', function () {
            /** @var MigrationRepositoryInterface $repository */
            $repository = $this->container->get('migration.repository');

            return new Migrator($repository, $this->app['db'], $this->app['files']);
        });

        $this->app['migrator'] = function () { return $this->container['migrator']; };
    }

    /**
     * Register the "refresh" migration command.
     *
     * @return void
     */
    protected function registerRefreshCommand()
    {
        $this->container->singleton('command.migrate.refresh', function () {
            return new RefreshCommand;
        });

        $this->app['command.migrate.refresh'] = function () { return $this->container['command.migrate.refresh']; };
    }

    /**
     * Register the migration repository service.
     */
    protected function registerRepository()
    {
        $this->container->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($this->app['db'], $table);
        });

        $this->app['migration.repository'] = function () { return $this->container['migration.repository']; };
    }

    /**
     * Register the "reset" migration command.
     *
     * @return void
     */
    protected function registerResetCommand()
    {
        $this->container->singleton('command.migrate.reset', function () {
            return new ResetCommand($this->container->get('migrator'));
        });

        $this->app['command.migrate.reset'] = function () { return $this->container['command.migrate.reset']; };
    }

    /**
     * Register the "rollback" migration command.
     *
     * @return void
     */
    protected function registerRollbackCommand()
    {
        $this->container->singleton('command.migrate.rollback', function () {
            return new RollbackCommand($this->container->get('migrator'));
        });

        $this->app['command.migrate.rollback'] = function () { return $this->container['command.migrate.rollback']; };
    }

    /**
     * Register the "status" migration command.
     *
     * @return void
     */
    protected function registerStatusCommand()
    {
        $this->container->singleton('command.migrate.status', function () {
            return new StatusCommand($this->container->get('migrator'));
        });
    }

}
