<?php namespace F9\Support\Provider;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory as IlluminateViewFactory;
use Illuminate\View\FileViewFinder;
use Nine\Views\Blade;
use Nine\Views\BladeConfigurationSet;
use Nine\Views\BladeView;
use Nine\Views\BladeViewConfigurationInterface;
use Pimple\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class BladeViewServiceProvider extends ServiceProvider
{
    /**
     * @param Container $app
     */
    public function boot(Container $app)
    {
        // only if enabled
        if ($app['config']['view.blade.enabled']) {

            // default blade context to simplify creating a new Blade|BladeView object.
            $app['blade.context'] = function ($app) {
                return new BladeConfigurationSet([
                    'engine'   => $app['blade.engine'],
                    'events'   => $app['illuminate.events'],
                    'factory'  => $app['blade.factory'],
                    'finder'   => $app['view.finder'],
                    'global'   => $app['global.scope'],
                    'paths'    => $app['paths'],
                    'settings' => $app['blade.settings'],
                ]);
            };

            $this->container
                ->add([BladeConfigurationSet::class, BladeViewConfigurationInterface::class],
                    function () use ($app) { return $app['blade.context']; });

            $this->container
                ->add([BladeView::class, 'BladeView'],
                    function () use ($app) { return new BladeView($app['blade.context']); });

            // for dependency injection. ie: DI::make(BladeView::class)
            $app[BladeViewConfigurationInterface::class] = function ($app) { return $app['blade.context']; };

            $app['blade'] = $app->factory(function ($app) { return new Blade($app['blade.context']); });
            $app['blade.view'] = $app->factory(function ($app) { return new BladeView($app['blade.context']); });
        }
    }

    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        // we only need to do this if Blade views are enabled.
        if ($app['config']['view.blade.enabled']) {

            $app['blade.settings'] = $app['config']['view.blade.defaults'];
            $app['blade.template.paths'] = $app['blade.settings']['template_paths'];

            $app['files'] = function () { return new Filesystem; };
            $app['view.finder'] = function ($app) { return new FileViewFinder(new Filesystem, $app['blade.template.paths']); };

            // create the Blade compiler using Filesystem and cache directory
            $app['blade.compiler'] = function ($app) { return new BladeCompiler(new Filesystem, $app['blade.settings']['cache']); };

            // get a blade compiler engine instance
            $app['blade.engine'] = function ($app) { return new CompilerEngine($app['blade.compiler']); };

            $app['view.engine.resolver'] = function ($app) {
                $resolver = new EngineResolver;
                $resolver->register('php', function () { return new PhpEngine; });
                $resolver->register('blade',
                    function () use ($app) { return new CompilerEngine($app['blade.compiler'], $app['files']); });

                return $resolver;
            };

            $app['blade.factory'] = function ($app) {
                $view_factory =
                    new IlluminateViewFactory ($app['view.engine.resolver'], $app['view.finder'], $app['illuminate.events']);

                $view_factory->setContainer($app['nine.container']);

                return $view_factory;
            };
        }

    }
}
