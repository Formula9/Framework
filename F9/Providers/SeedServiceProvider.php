<?php namespace F9\Providers;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Support\Provider\ServiceProvider;
use Illuminate\Console\Events\ArtisanStarting;
use Nine\Database\Console\Seeds\SeedCommand;
use Nine\Database\Console\Seeds\Seeder;
use Pimple\Container;

class SeedServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = TRUE;

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['seeder', 'command.seed'];
    }

    /**
     * Register the service provider.
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $this->container->singleton('seeder', function () {
            $seeder = Seeder::class;
            return new $seeder;
        });

        $this->registerSeedCommand();

        $this->commands('command.seed');
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
     * Register the seed console command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {

        $this->container->singleton('command.seed', function () {
            return new SeedCommand($this->container->get('illuminate.connection.resolver'));
        });
    }
}
