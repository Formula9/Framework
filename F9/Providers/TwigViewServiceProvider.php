<?php namespace F9\Support\Provider;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Application\Application;
use F9\Contracts\BootableProvider;
use Nine\Containers\Forge;
use Nine\Views\TwigConfigurationSet;
use Nine\Views\TwigView;
use Nine\Views\TwigViewConfigurationInterface;
use Pimple\Container;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\DumpExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Fragment\HIncludeFragmentRenderer;

class TwigViewServiceProvider extends ServiceProvider implements BootableProvider
{

    /**
     * @param Application|Container $app
     *
     */
    public function boot($app)
    {
        /** @var Forge $container */
        list($config, $container) = [$this->config, $this->container];

        // only boot if twig templates are enabled by the framework
        if ($this->config['view.twig.enabled']) {

            $app['twig.context'] = function ($app) {
                return new TwigConfigurationSet([
                    'events'   => $app['nine.events'],
                    'finder'   => $app['twig.finder'],
                    'global'   => $app['global.scope'],
                    'paths'    => $app['paths'],
                    'settings' => $app['twig.settings'],
                ]);
            };

            $app['twig.view'] = $app->factory(function ($app) { return new TwigView($app['twig.context']); });

            $container->singleton([TwigConfigurationSet::class, TwigViewConfigurationInterface::class],
                function () use ($app) { return $app['twig.context']; });

            $container->add([TwigView::class, 'TwigView'],
                function () use ($app) { return new TwigView($app['twig.context']); });

        }
    }

    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        // only register if twig templates are enabled by the framework
        if ($this->config['view.twig.enabled']) {

            $app['twig.settings'] = $settings = $this->config['view.twig.defaults'];
            $app['twig.options'] = $this->config['view.twig.defaults.options'];
            $app['twig.template.paths'] = $app['twig.settings']['filesystem'];
            $app['twig.form.templates'] = $app['twig.settings']['form']['templates']; # 'form_div_layout.html.twig'];
            $app['twig.templates'] = $app['twig.settings']['templates'];

            $app->register(new TranslationServiceProvider, [
                'locale_fallbacks' => ['en'],
            ]);

            $app['twig.app_variable'] = function ($app) {
                $var = new AppVariable();
                if (isset($app['security.token_storage'])) {
                    $var->setTokenStorage($app['security.token_storage']);
                }

                if (isset($app['request_stack'])) {
                    $var->setRequestStack($app['request_stack']);
                }

                $var->setDebug($app['debug']);

                return $var;
            };

            $app['twig.finder'] = $app->protect(function ($template) use ($app) {
                // try locating the
                $finder = new Finder();

                // add twig template paths
                foreach ($app['twig.template.paths'] as $dir) {
                    $finder->in(rtrim($dir, '/'));
                }

                // look for a match
                /** @var \SplFileInfo $file */
                foreach ($finder->files() as $file) {
                    if ($file->getFilename() === $template) {
                        return TRUE;
                    }
                }

                return FALSE;
            });

            $app['twig.loader.filesystem'] = function ($app) {
                return new \Twig_Loader_Filesystem($app['twig.template.paths']);
            };

            $app['twig.loader.array'] = function ($app) {
                return new \Twig_Loader_Array($app['twig.templates']);
            };

            $app['twig.loader'] = function ($app) {
                return new \Twig_Loader_Chain([
                    $app['twig.loader.array'],
                    $app['twig.loader.filesystem'],
                ]);
            };

            $app['twig.environment_factory'] = $app->protect(function ($app) {
                return new \Twig_Environment($app['twig.loader'], $app['twig.options']);
            });

            $app['twig'] = function ($app) {

                /** @var \Twig_Environment $twig */
                $twig = $app['twig.environment_factory']($app);

                // registered for BC, but should not be used anymore
                // deprecated and should probably be removed in Silex 3.0
                $twig->addGlobal('app', $app);

                if ($app['debug']) {
                    $twig->addExtension(new \Twig_Extension_Debug());
                }

                if (class_exists('Symfony\Bridge\Twig\Extension\RoutingExtension')) {
                    $twig->addGlobal('global', $app['twig.app_variable']);

                    if (isset($app['request_stack'])) {
                        $twig->addExtension(new HttpFoundationExtension($app['request_stack']));
                        $twig->addExtension(new RoutingExtension($app['url_generator']));
                    }

                    if (isset($app['translator'])) {
                        $twig->addExtension(new TranslationExtension($app['translator']));
                    }

                    if (isset($app['security.authorization_checker'])) {
                        $twig->addExtension(new SecurityExtension($app['security.authorization_checker']));
                    }

                    if (isset($app['fragment.handler'])) {
                        /** @type HIncludeFragmentRenderer */
                        $app['fragment.renderer.hinclude']->{'setTemplating'}($twig);

                        $twig->addExtension(new HttpKernelExtension($app['fragment.handler']));
                    }

                    if (isset($app['assets.packages'])) {
                        $twig->addExtension(new AssetExtension($app['assets.packages']));
                    }

                    if (isset($app['form.factory'])) {
                        $app['twig.form.engine'] = function ($app) {
                            return new TwigRendererEngine($app['twig.form.templates']);
                        };

                        $app['twig.form.renderer'] = function ($app) {
                            return new TwigRenderer($app['twig.form.engine'], $app['csrf.token_manager']);
                        };

                        $twig->addExtension(new FormExtension($app['twig.form.renderer']));

                        // add loader for Symfony built-in form templates
                        $reflected = new \ReflectionClass('Symfony\Bridge\Twig\Extension\FormExtension');
                        $path = dirname($reflected->getFileName()) . '/../Resources/views/Form';

                        /** @var \Twig_Loader_Chain */
                        $app['twig.loader']->{'addLoader'}(new \Twig_Loader_Filesystem($path));
                    }

                    if (isset($app['var_dumper.cloner'])) {
                        $twig->addExtension(new DumpExtension($app['var_dumper.cloner']));
                    }
                }

                return $twig;
            };
        }

    }
}
