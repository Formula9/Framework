<?php namespace Nine\Views;

/**
 * @package Noid
 * @version 0.1.0
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use Nine\Collections\Scope;

/**
 * **BladeView is an implementation of the Illuminate View component.**
 *
 * Requires that `BladeViewServiceProvider` has been previously registered.
 *
 * Usage:
 *
 *      $bv = app('blade.view');
 *      - or -
 *      $bv = new BladeView(new BladeContext([...]));
 *
 *      $bv['data'] = 'test data';
 *      $bv->render('template');
 *      - or -
 *      $bv->with('data','test data')->render('template');
 *      - or -
 *      $bv->template('test')->with('data','testing')->render();
 *      - or -
 *      app('blade.view')->template('test')->with('data','testing')->render();
 *
 * @see `view()` function.
 */
class BladeView extends AbstractView
{
    /** @var Scope|BladeConfigurationSet|BladeViewConfigurationInterface */
    protected $context;

    /** @var Scope */
    protected $scope;

    /** @var string */
    private $template;

    /** @var FileViewFinder - the Blade view finder */
    private $viewFinder;

    /**
     * Construct a compatible environment for Blade template rendering by
     * connecting to COGS resources and the illuminate/container/container.
     *
     * @param BladeConfigurationSet|BladeViewConfigurationInterface|Scope $context
     */
    public function __construct(BladeViewConfigurationInterface $context)
    {
        $this->context = $context;

        parent::__construct($context);
        $this->configure($context);
    }

    /**
     * Append or Prepend (default) a path to the BladeView template_paths setting.
     *
     * @param string $path    : path to append to the template_path setting
     * @param bool   $prepend : TRUE to push the new path on the top, FALSE to append
     *
     * @return array
     */
    public function addViewPath($path, $prepend = TRUE) : array
    {
        parent::addViewPath($path, $prepend);

        // as far as I can tell, we need to reconstruct the FileViewFinder
        unset($this->viewFinder);
        $this->viewFinder = new FileViewFinder(new Filesystem, $this->templatePaths);

        return $this->templatePaths;
    }

    /**
     * **Embed a rendered view into the common view scope.**
     *
     * Usage:
     *      $blade_view = new BladeView(new BladeContext([...]));
     *      $blade_view->embed('name','sub_view',[...]);
     *
     *      // where 'main_view' includes {!! $name !!} in the template.
     *      $blade_view->render('main_view', [...]);
     *
     * @param string $symbol The variable name supplied to scope.
     * @param string $view   The name of the view to render.
     * @param array  $data   [optional] Data to include in the view.
     *
     * @return string
     */
    public function embed($symbol, $view, $data = [])
    {
        $embed = $this->makeView($view, $data)->render();
        $this[$symbol] = $embed;

        return $embed;
    }

    /**
     * **Get the array of paths from the BladeView view finder.**
     *
     * @return array
     */
    public function getViewPaths() : array
    {
        return $this->viewFinder->getPaths();
    }

    /**
     * **Determine if a template exists in any of the template folders.**
     *
     * @param string $template
     *
     * @return bool
     */
    public function hasView($template) : bool
    {
        try {
            $this->viewFinder->find($template);
        } catch (\InvalidArgumentException $e) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * **Make a new View.**
     *
     * Note: __This method is used internally to make views.__
     *
     * The purpose for this is to allow making changes to the View
     * object before rendering. ie:
     *
     *      $view = app('blade.view')->makeView('test',['data'=>'some data']);
     *      $view->nest('key','view',['test' => 'test data']);
     *      return $view->render();
     *
     * @param       $view
     * @param array $data
     *
     * @return View
     */
    public function makeView($view, array $data)
    {
        $data = parent::collectScope($data);

        return $this->context->factory()->make($view, $data);
    }

    /**
     * Renders a Blade template with passed and stored symbol data.
     *
     * @param string $view - view name i.e.: 'sample' resolves to [template_path]/sample.blade.php
     * @param array  $data
     *
     * @return string
     */
    public function render($view = NULL, array $data = []) : string
    {
        $data = $this->collectScope($data);

        // use the template supplied with ->using() if one is not provided
        if (NULL === $view) {
            $view = $this->template;
        }

        // one way or the other, there must be a template to render
        if (NULL === $view) {
            new \InvalidArgumentException('No view template supplied.');
        }

        return $this->makeView($view, $data)->render();
    }

    /**
     * @param string $view
     *
     * @return $this
     */
    public function template($view = 'default')
    {
        $this->template = $view;

        return $this;
    }

    /**
     * @param BladeViewConfigurationInterface $context
     */
    protected function configure(BladeViewConfigurationInterface $context)
    {
        $this->templatePaths = $context->paths();
        $this->events = $context->events();
        $this->viewFinder = $context->finder();
        $this->scope = $context->globalScope();
    }

}
