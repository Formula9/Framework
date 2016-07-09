<?php namespace F9\Support\Provider;

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
 */

use F9\Application\Application;
use F9\Contracts\BootableProvider;
use Nine\Views\TwigView;
use Pimple\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class ViewServiceProvider extends ServiceProvider implements BootableProvider
{
    public function boot($app)
    {
        if ($this->config['view.twig.enabled'] && class_exists(TwigViewServiceProvider::class)) {
            $app['twig.view'] = $app->factory(function ($app) { return new TwigView($app['twig.context']); });
        }
    }

    public function register(Container $app)
    {
        $config = $this->config;

        // optionally include the Blade templating engine.
        if ($config['view.blade.enabled'] && class_exists(BladeViewServiceProvider::class)) {
            $app->register(new BladeViewServiceProvider($this->app));
            $this->app->bootProvider(BladeViewServiceProvider::class);
        }

        // optionally include the Twig templating engine
        if ($config['view.twig.enabled'] && class_exists(TwigViewServiceProvider::class)) {

            $app->register(new TwigViewServiceProvider($this->app));

            $app['twig.options'] = $config['view.twig.defaults.options'];
            $app['twig.form.templates'] = $config['view.twig.defaults.form.templates'];
            $app['twig.path'] = $config['view.twig.defaults.filesystem'];
            $app['twig.templates'] = $config['view.twig.defaults.templates'];

            $this->app->bootProvider(TwigViewServiceProvider::class);

            $app['twig'] = $app->extend('twig', function ($twig, $app) {

                // during normal routing, the current request is non-NULL.
                // however, there are test conditions under which we still need
                // access to the base path, so here we cover that use case.
                $current_request = $app['request_stack']->getCurrentRequest();
                $base_path = $current_request ? $current_request->getBasePath() : '';

                // add the `asset` function for twig
                // The asset function takes a path to a resource based on the ASSETS constant path.
                $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app, $base_path) {
                    $base = $base_path . ASSETS;

                    return sprintf($base . $asset, ltrim($asset, '/'));
                }));

                // The asset function takes a path to a resource based on the ASSETS constant path.
                $twig->addFunction(new \Twig_SimpleFunction('image', function ($asset) use ($app, $base_path) {
                    $base = $base_path . IMAGES;

                    return sprintf($base . $asset, ltrim($asset, '/'));
                }));

                // The asset function takes a path to a resource based on the ASSETS constant path.
                $twig->addFunction(new \Twig_SimpleFunction('script', function ($asset) use ($app, $base_path) {
                    $base = $base_path . SCRIPTS;

                    return sprintf($base . $asset, ltrim($asset, '/'));
                }));

                // The asset function takes a path to a resource based on the ASSETS constant path.
                $twig->addFunction(new \Twig_SimpleFunction('style', function ($asset) use ($app, $base_path) {
                    $base = $base_path . STYLESHEETS;

                    return sprintf($base . $asset, ltrim($asset, '/'));
                }));

                // The asset function takes a path to a resource based on the ASSETS constant path.
                $twig->addFunction(new \Twig_SimpleFunction('glyph', function ($glyph) use ($app) {
                    return "<span class='glyphicon glyphicon-$glyph'></span>";
                }));

                return $twig;
            });
        }
    }
}
