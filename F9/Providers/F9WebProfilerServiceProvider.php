<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use F9\Application\Application as NineApplication;
use Silex\ServiceControllerResolver;
use Symfony\Bridge\Twig\DataCollector\TwigDataCollector;
use Symfony\Bridge\Twig\Extension\CodeExtension;
use Symfony\Bridge\Twig\Extension\ProfilerExtension;
use Symfony\Bundle\WebProfilerBundle\Controller\ExceptionController;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Bundle\WebProfilerBundle\Controller\RouterController;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;
use Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\DataCollector\FormDataCollector;
use Symfony\Component\Form\Extension\DataCollector\FormDataExtractor;
use Symfony\Component\Form\Extension\DataCollector\Proxy\ResolvedTypeFactoryDataCollectorProxy;
use Symfony\Component\Form\Extension\DataCollector\Type\DataCollectorTypeExtension;
use Symfony\Component\HttpKernel\DataCollector\AjaxDataCollector;
use Symfony\Component\HttpKernel\DataCollector\ConfigDataCollector;
use Symfony\Component\HttpKernel\DataCollector\DumpDataCollector;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\DataCollector\MemoryDataCollector;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\DataCollector\RouterDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Symfony\Component\HttpKernel\EventListener\DumpListener;
use Symfony\Component\HttpKernel\EventListener\ProfilerListener;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Yaml;

/**
 * Symfony Web Profiler provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class F9WebProfilerServiceProvider implements ServiceProviderInterface, ControllerProviderInterface, BootableProviderInterface, EventListenerProviderInterface
{
    public function boot(Application $app)
    {
        $app->mount($app['profiler.mount_prefix'], $this->connect($app));
    }

    public function connect(Application $app)
    {
        if ( ! $app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use the WebProfiler.');
        }

        $controllers = $app['controllers_factory'];

        $controllers->get('/router/{token}', 'web_profiler.controller.router:panelAction')->bind('_profiler_router');
        $controllers->get('/exception/{token}.css', 'web_profiler.controller.exception:cssAction')->bind('_profiler_exception_css');
        $controllers->get('/exception/{token}', 'web_profiler.controller.exception:showAction')->bind('_profiler_exception');
        $controllers->get('/search', 'web_profiler.controller.profiler:searchAction')->bind('_profiler_search');
        $controllers->get('/search_bar', 'web_profiler.controller.profiler:searchBarAction')->bind('_profiler_search_bar');
        $controllers->get('/purge', 'web_profiler.controller.profiler:purgeAction')->bind('_profiler_purge');
        $controllers->get('/info/{about}', 'web_profiler.controller.profiler:infoAction')->bind('_profiler_info');
        $controllers->get('/phpinfo', 'web_profiler.controller.profiler:phpinfoAction')->bind('_profiler_phpinfo');
        $controllers->get('/{token}/search/results', 'web_profiler.controller.profiler:searchResultsAction')->bind('_profiler_search_results');
        $controllers->get('/{token}', 'web_profiler.controller.profiler:panelAction')->bind('_profiler');
        $controllers->get('/wdt/{token}', 'web_profiler.controller.profiler:toolbarAction')->bind('_wdt');
        $controllers->get('/', 'web_profiler.controller.profiler:homeAction')->bind('_profiler_home');

        return $controllers;
    }

    public function register(Container $app)
    {
        $app['profiler.mount_prefix'] = '/_profiler';
        $app->extend('dispatcher', function ($dispatcher, $app) {
            return new TraceableEventDispatcher($dispatcher, $app['stopwatch'], $app['logger']);
        });

        $app['data_collector.templates'] = function ($app) {
            $templates = [
                ['config', '@WebProfiler/Collector/config.html.twig'],
                ['request', '@WebProfiler/Collector/request.html.twig'],
                ['exception', '@WebProfiler/Collector/exception.html.twig'],
                ['events', '@WebProfiler/Collector/events.html.twig'],
                ['logger', '@WebProfiler/Collector/logger.html.twig'],
                ['time', '@WebProfiler/Collector/time.html.twig'],
                ['router', '@WebProfiler/Collector/router.html.twig'],
                ['memory', '@WebProfiler/Collector/memory.html.twig'],
                ['form', '@WebProfiler/Collector/form.html.twig'],
            ];

            if (class_exists('Symfony\Bridge\Twig\Extension\ProfilerExtension')) {
                $templates[] = ['twig', '@WebProfiler/Collector/twig.html.twig'];
            }

            if (isset($app['var_dumper.cli_dumper']) && $app['profiler.templates_path.debug']) {
                $templates[] = ['dump', '@Debug/Profiler/dump.html.twig'];
            }

            if (class_exists('Symfony\Component\HttpKernel\DataCollector\AjaxDataCollector')) {
                $templates[] = ['ajax', '@WebProfiler/Collector/ajax.html.twig'];
            }

            return $templates;
        };

        $app['data_collectors'] = function ($app) {
            return [
                'config'    => function ($app) { return new ConfigDataCollector('Formula Nine', NineApplication::VERSION); },
                'request'   => function ($app) { return new RequestDataCollector(); },
                'exception' => function ($app) { return new ExceptionDataCollector(); },
                'events'    => function ($app) { return new EventDataCollector($app['dispatcher']); },
                'logger'    => function ($app) { return new LoggerDataCollector($app['logger']); },
                'time'      => function ($app) { return new TimeDataCollector(NULL, $app['stopwatch']); },
                'router'    => function ($app) { return new RouterDataCollector(); },
                'memory'    => function ($app) { return new MemoryDataCollector(); },
            ];
        };

        if (isset($app['form.resolved_type_factory']) && class_exists('\Symfony\Component\Form\Extension\DataCollector\FormDataCollector')) {
            $app['data_collectors.form.extractor'] = function () { return new FormDataExtractor(); };

            $app->extend('data_collectors', function ($collectors, $app) {
                $collectors['form'] = function ($app) { return new FormDataCollector($app['data_collectors.form.extractor']); };

                return $collectors;
            });

            $app->extend('form.resolved_type_factory', function ($factory, $app) {
                return new ResolvedTypeFactoryDataCollectorProxy($factory, $app['data_collectors']['form']($app));
            });

            $app->extend('form.type.extensions', function ($extensions, $app) {
                $extensions[] = new DataCollectorTypeExtension($app['data_collectors']['form']($app));

                return $extensions;
            });
        }

        if (class_exists('Symfony\Bridge\Twig\Extension\ProfilerExtension')) {
            $app['data_collectors'] = $app->extend('data_collectors', function ($collectors, $app) {
                $collectors['twig'] = function ($app) {
                    return new TwigDataCollector($app['twig.profiler.profile']);
                };

                return $collectors;
            });

            $app['twig.profiler.profile'] = function () {
                return new \Twig_Profiler_Profile();
            };
        }

        if (isset($app['var_dumper.cli_dumper'])) {
            $app['var_dumper.dump_listener'] = function ($app) {
                return new DumpListener($app['var_dumper.cloner'], $app['var_dumper.data_collector']);
            };

            $app['data_collectors'] = $app->extend('data_collectors', function ($collectors, $app) {
                if ($app['profiler.templates_path.debug']) {
                    $collectors['dump'] = function ($app) {
                        $dumper = NULL === $app['var_dumper.dump_destination'] ? NULL : $app['var_dumper.cli_dumper'];

                        return $app['var_dumper.data_collector'] = new DumpDataCollector($app['stopwatch'], NULL, $app['charset'], $app['request_stack'], $dumper);
                    };
                }

                return $collectors;
            });
        }

        if (class_exists('Symfony\Component\HttpKernel\DataCollector\AjaxDataCollector')) {
            $app['data_collectors'] = $app->extend('data_collectors', function ($collectors, $app) {
                $collectors['ajax'] = function ($app) {
                    return new AjaxDataCollector();
                };

                return $collectors;
            });
        }

        if (isset($app['security.token_storage']) && class_exists('Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector')) {
            $app->extend('data_collectors', function ($collectors, $app) {
                $collectors['security'] = function ($app) {
                    $roleHierarchy = ! empty($app['security.role_hierarchy']) ? $app['security.role_hierarchy'] : NULL;
                    $logoutUrlGenerator = new LogoutUrlGenerator($app['request_stack'], $app['url_generator'], $app['security.token_storage']);

                    $sec_data_collector = 'Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector';

                    return new $sec_data_collector($app['security.token_storage'], $roleHierarchy, $logoutUrlGenerator);
                };

                return $collectors;
            });

            $app->extend('data_collector.templates', function ($templates) {
                $templates[] = ['security', '@Security/Collector/security.html.twig'];

                return $templates;
            });

            $app->extend('twig.loader.filesystem', function ($loader, $app) {
                if ($app['profiler.templates_path.security']) {
                    $loader->addPath($app['profiler.templates_path.security'], 'Security');
                }

                return $loader;
            });

            $app['profiler.templates_path.security'] = function () {
                $r = new \ReflectionClass('Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector');

                return dirname(dirname($r->getFileName())) . '/Resources/views';
            };

            $app['twig'] = $app->extend('twig', function ($twig, $app) {
                $twig->addFilter('yaml_encode', new \Twig_SimpleFilter('yaml_encode', function (array $var) {
                    return Yaml::dump($var);
                }));

                $twig->addFunction('yaml_encode', new \Twig_SimpleFunction('yaml_encode', function (array $var) {
                    return Yaml::dump($var);
                }));

                return $twig;
            });
        }

        $app['web_profiler.controller.profiler'] = function ($app) {
            return new ProfilerController($app['url_generator'], $app['profiler'], $app['twig'], $app['data_collector.templates'], $app['web_profiler.debug_toolbar.position']);
        };

        $app['web_profiler.controller.router'] = function ($app) {
            return new RouterController($app['profiler'], $app['twig'], $app['request_matcher'] ?? NULL, $app['routes']);
        };

        $app['web_profiler.controller.exception'] = function ($app) {
            return new ExceptionController($app['profiler'], $app['twig'], $app['debug']);
        };

        $app['web_profiler.toolbar.listener'] = function ($app) {
            $mode = $app['web_profiler.debug_toolbar.enable'] ? WebDebugToolbarListener::ENABLED : WebDebugToolbarListener::DISABLED;

            return new WebDebugToolbarListener($app['twig'], $app['web_profiler.debug_toolbar.intercept_redirects'], $mode, $app['web_profiler.debug_toolbar.position'], $app['url_generator']);
        };

        $app['profiler'] = function ($app) {
            $profiler = new Profiler($app['profiler.storage'], $app['logger']);

            foreach ($app['data_collectors'] as $collector) {
                /** @var callable $collector */
                $profiler->add($collector($app));
            }

            return $profiler;
        };

        $app['profiler.storage'] = function ($app) {
            return new FileProfilerStorage('file:' . $app['profiler.cache_dir']);
        };

        $app['profiler.request_matcher'] = NULL;
        $app['profiler.only_exceptions'] = FALSE;
        $app['profiler.only_master_requests'] = FALSE;
        $app['web_profiler.debug_toolbar.enable'] = TRUE;
        $app['web_profiler.debug_toolbar.position'] = 'bottom';
        $app['web_profiler.debug_toolbar.intercept_redirects'] = FALSE;

        $app['profiler.listener'] = function ($app) {
            if (Kernel::VERSION_ID >= 20800) {
                return new ProfilerListener($app['profiler'], $app['request_stack'], $app['profiler.request_matcher'], $app['profiler.only_exceptions'], $app['profiler.only_master_requests']);
            }
            else {
                /** @noinspection PhpParamsInspection */
                return new ProfilerListener($app['profiler'], $app['profiler.request_matcher'], $app['profiler.only_exceptions'], $app['profiler.only_master_requests'], $app['request_stack']);
            }
        };

        $app['stopwatch'] = function () {
            return new Stopwatch();
        };

        $app['code.file_link_format'] = NULL;

        $app->extend('twig', function ($twig, $app) {
            $twig->addExtension(new CodeExtension($app['code.file_link_format'], '', $app['charset']));

            if (class_exists('\Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension')) {
                $twig->addExtension(new WebProfilerExtension());
            }

            if (class_exists('Symfony\Bridge\Twig\Extension\ProfilerExtension')) {
                $twig->addExtension(new ProfilerExtension($app['twig.profiler.profile'], $app['stopwatch']));
            }

            return $twig;
        });

        $app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath($app['profiler.templates_path'], 'WebProfiler');
            if ($app['profiler.templates_path.debug']) {
                $loader->addPath($app['profiler.templates_path.debug'], 'Debug');
            }

            return $loader;
        });

        $app['profiler.templates_path'] = function () {
            $r = new \ReflectionClass('Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener');

            return dirname(dirname($r->getFileName())) . '/Resources/views';
        };

        $app['profiler.templates_path.debug'] = function () {
            foreach (spl_autoload_functions() as $autoloader) {
                if ( ! is_array($autoloader) || ! method_exists($autoloader[0], 'findFile')) {
                    continue;
                }

                if ($file = $autoloader[0]->{'findFile'}('Symfony\Bundle\DebugBundle\DebugBundle')) {
                    return dirname($file) . '/Resources/views';
                }
            }
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['profiler.listener']);

        if ($app['web_profiler.debug_toolbar.enable']) {
            $dispatcher->addSubscriber($app['web_profiler.toolbar.listener']);
        }

        /** @noinspection PhpParamsInspection */
        $dispatcher->addSubscriber($app['profiler']->get('request'));

        if (isset($app['var_dumper.data_collector'])) {
            $dispatcher->addSubscriber($app['var_dumper.dump_listener']);
        }
    }
}
