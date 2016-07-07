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

use Nine\Collections\Scope;
use Nine\Events\Events;
use Symfony\Component\Finder\Finder;

/**
 * **AbstractView is the foundation of any of the Formula Nine views.**
 *
 */
abstract class AbstractView extends Scope
{
    const VIEW_RENDER_EVENT = 'view.render.event';

    /** @var ViewConfigurationInterface */
    protected $context;

    /** @var Events */
    protected $events;

    /** @var null|string */
    protected $name;

    /** @var Scope */
    protected $scope;

    /** @var array - a list of paths to search for the template file */
    protected $templatePaths = [];

    /**
     * AbstractView constructor.
     *
     * @param ViewConfigurationInterface $context
     */
    public function __construct(ViewConfigurationInterface $context)
    {
        $this->context = $context;

        // Context - for symbols and variables
        parent::__construct($context->settings());

        $this->scope = $context->globalScope();
        $this->events = $context->events();
    }

    /**
     * Implements a value-as-method syntax.
     * ie: context->{thing}('is this') where 'thing' exists in the context.
     *
     * Returns the assigned value.
     *
     * This funky hint-of-templates syntax implements the following:
     *
     *      view->{'thing'} = 'string'
     *      view->{'thing'}('string')[->...]
     *
     * @param  string $method
     * @param  array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $this[$method] = count($arguments) > 0 ? $arguments[0] : TRUE;

        return $this[$method];
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
        $prepend_or_append = $prepend ? 'array_unshift' : 'array_push';
        $prepend_or_append($this->templatePaths, $path);

        return $this->templatePaths;
    }

    /**
     * Collect content from the shared View Scope (etc.)
     *
     * @param array $merge_data
     *
     * @return mixed
     */
    public function collectScope(array $merge_data = [])
    {
        return $this->scope->merge($merge_data);
    }

    /**
     * get the array of paths from the BladeView view finder.
     *
     * @return array
     */
    abstract public function getViewPaths() : array;

    /**
     * @param string $template
     *
     * @return bool
     */
    public function hasView($template) : bool
    {
        $finder = new Finder();
        $finder->in($this->templatePaths);

        return $finder->contains($template) or $finder->contains("$template.php");
    }

    /**
     * **Renders a template with passed and stored symbol data.**
     *
     * @param string $view - view name i.e.: 'sample' resolves to [template_path]/sample.blade.php
     * @param array  $data
     *
     * @return string
     */
    abstract public function render($view, array $data = []) : string;

    /**
     * @param $data
     */
    public function share($data)
    {
        $this->context->globalScope()->importValue($data);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return AbstractView|static
     */
    public function with($name, $value) : AbstractView
    {
        $this->scope->merge([$name => $value]);

        return $this;
    }

}
