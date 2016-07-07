<?php namespace Nine\Views;

/**
 * F9 (Formula Nine) Personal PHP Framework
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
 *
 * @package Noid
 * @version 0.1.0
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Twig_Environment;
use Twig_Error_Runtime;
use Twig_Loader_Filesystem;

/**
 * **TwigView is an implementation of the Illuminate View component.**
 *
 * Requires that `TwigViewServiceProvider` has been previously registered.
 *
 * Usage:
 *
 *      $tv = app('twig.view');
 *      - or -
 *      $tv = new TwigView(new TwigConfigurationSet([...]));
 *
 *      $tv['data'] = 'test data';
 *      $tv->render('template');
 *      - or -
 *      $tv->with('data','test data')->render('template');
 *      - or -
 *      $tv->template('test')->with('data','testing')->render();
 *      - or -
 *      app('twig.view')->template('test')->with('data','testing')->render();
 *
 * @see `view()` function.
 */
class TwigView extends AbstractView
{
    const TWIG_LOADER_ARRAY  = 1;
    const TWIG_LOADER_STRING = 2;
    const TWIG_LOADER_CHAIN  = 3;
    const TWIG_LOADER_FILE   = 4;
    const TWIG_LOADER_RADIUM = 5;

    /** @var TwigConfigurationSet */
    protected $context;

    /** @var array */
    protected $defaults;

    /** @var array */
    protected $environment;

    /** @var array */
    protected $filesystem;

    /** @var string */
    protected $template;

    /** @var int */
    protected $template_type;

    /** @var Twig_Environment */
    protected $twig;

    /** @var Twig_Loader_Filesystem */
    protected $twig_loader;

    public function __construct(TwigViewConfigurationInterface $context)
    {
        $this->context = $context;
        $this->defaults = $context->settings();

        $this->templatePaths = $this->defaults['filesystem'];
        $this->events = $context->events();
        parent::__construct($context);

        $this->configure();

    }

    /**
     * @param $templateDir
     *
     * @return mixed|void
     */
    public function addPath($templateDir)
    {
        $this->twig_loader->addPath(realpath($templateDir));

        return $this;
    }

    /**
     * @param array $template_paths
     *
     * @return mixed|void
     * @throws \Twig_Error_Loader
     */
    public function addPaths($template_paths)
    {
        foreach ($template_paths as $template_path) {
            $this->twig_loader->addPath($template_path);
        }

        return $this;
    }

    /**
     * @return \Twig_Loader_Filesystem
     */
    public function getTwigLoader()
    {
        return $this->twig_loader;
    }

    /**
     * get the array of paths from the BladeView view finder.
     *
     * @return array
     */
    public function getViewPaths() : array
    {
        return $this->getTwigLoader()->getPaths();
    }

    /**
     * @param string $template_name
     *
     * @return mixed
     * @throws Twig_Error_Runtime
     */
    public function hasView($template_name) : bool
    {
        throw new \Twig_Error_Runtime('hasView not implemented.');
    }

    /**
     * Template Service Load Interface
     *
     * @param $template_file
     *
     * @return mixed
     */
    public function loadTemplate($template_file)
    {
        try {
            $this->twig->loadTemplate($template_file);

            return $this;
        } catch (\Twig_Error_Loader $e) {
            return FALSE;
        }
    }

    /**
     * @param $templateDir
     *
     * @return mixed|void
     */
    public function prependPath($templateDir)
    {
        $this->twig_loader->prependPath(realpath($templateDir));

        return $this;
    }

    /**
     * Renders a Blade template with passed and stored symbol data.
     *
     * @param null|string $view
     * @param array       $data
     *
     * @return string
     * @internal param string $name
     */
    public function render($view = NULL, array $data = []) : string
    {
        // use the template supplied with ->using() if one is not provided
        $view = $view ?: $this->template;

        // one way or the other, there must be a template to render
        if (NULL === $view) {
            throw new \InvalidArgumentException('No twig view template supplied.');
        }

        $name = empty(pathinfo($view, PATHINFO_EXTENSION)) ? $view . '.twig' : $view;
        $data = $this->collectScope($data);

        return $this->twig->render($name, $data);
    }

    /**
     * @param string $view
     *
     * @return TwigView
     */
    public function using($view = 'default') : TwigView
    {
        $this->template = $view;

        return $this;
    }

    /**
     * Sends configuration data to the template engine
     *
     * for twig, this should include ['filesystem'=>['',],'environment'=>'',]
     *
     */
    protected function configure()
    {
        // set the twig environment (templates and loaders)
        $this->set_environment($this->defaults);

        // make twig and install extensions and globals
        $this->make_twig();
    }

    /**
     * @param $configuration
     *
     * @return mixed
     */
    private function get_template_type($configuration)
    {
        // the 'type' parameter determines which loader is used
        /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
        $this->template_type = isset($configuration['type']) ? $configuration['type'] : static::TWIG_LOADER_FILE;

        return $configuration;
    }

    /**
     *  creates the twig environment, sets globals and installs extensions
     */
    private function make_twig()
    {
        // \Twig_Autoloader::register();
        $this->twig = new \Twig_Environment($this->twig_loader, array_merge($this->environment, ['debug' => TRUE]));

        // globals
        $this->set_globals();

        // extensions
        $this->twig->addExtension(new \Twig_Extension_Debug());
    }

    /**
     * @param $configuration
     */
    private function set_environment($configuration)
    {
        $configuration = $this->get_template_type($configuration);

        // get the loader
        $configuration = $this->set_loader($configuration);

        // assign the environment
        /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
        $this->environment = isset($configuration['environment']) ? $configuration['environment'] : [];
    }

    private function set_globals()
    {
        $this->twig->addGlobal('forge', forge());
        $this->twig->addGlobal('context', forge('context'));
    }

    /**
     * @param $configuration
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    private function set_loader($configuration)
    {
        switch (TRUE) {

            case ($this->template_type === static::TWIG_LOADER_ARRAY):

                if ( ! array_key_exists('array', $configuration)) {
                    throw new \InvalidArgumentException(
                        'Twig Service Configuration Error: TWIG_LOADER_ARRAY `array` setting is missing.');
                }

                $this->twig_loader = new \Twig_Loader_Array($configuration['array']);
                break;

            case ($this->template_type === static::TWIG_LOADER_STRING):

                throw new \InvalidArgumentException('Twig template type `TWIG_LOADER_STRING` is deprecated.');
                //$this->twig_loader = new \Twig_Loader_String();
                break;

            case ($this->template_type === static::TWIG_LOADER_CHAIN):

                if ( ! array_key_exists('chain', $configuration)) {
                    throw new \InvalidArgumentException(
                        'Twig Service Configuration Error: TWIG_LOADER_CHAIN `chain` setting is missing.');
                }

                $loaders = [];
                array_map(
                    function ($loader) use (&$loaders) {
                        $loaders[] = new \Twig_Loader_Array($loader);
                    },
                    $configuration['chain']
                );

                $this->twig_loader = new \Twig_Loader_Chain($loaders);
                break;

            case ($this->template_type === static::TWIG_LOADER_FILE):

                if ( ! array_key_exists('filesystem', $configuration)) {
                    new \InvalidArgumentException(
                        'Twig Service Configuration Error: TWIG_LOADER_FILE "filesystem" setting is missing.');
                }

                /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
                $this->filesystem = isset($configuration['filesystem']) ? $configuration['filesystem'] : [];
                $this->twig_loader = new \Twig_Loader_Filesystem($this->filesystem);
                break;

            case ($this->template_type === static::TWIG_LOADER_RADIUM):
                throw new \InvalidArgumentException('Twig Service Error: TWIG_LOADER_RADIUM not implemented.');
                break;
        }

        return $configuration;
    }
}
