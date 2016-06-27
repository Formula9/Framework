<?php namespace Nine\Views;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * **Blade - a simple Illuminate Blade View.**
 *
 * Requires that `BladeViewServiceProvider` has been previously registered.
 *
 * **Note**: _Does not incorporate the global scope._
 *
 * Usage:
 *
 *      $bv = app('blade');
 *      - or -
 *      $bv = new Blade(config('view.blade.defaults'));
 *
 *      $bv['data'] = 'test data';
 *      $bv->render('template');
 *      - or -
 *      $bv->with('data','test data')->render('template');
 *      - or -
 *      $bv->template('test')->with('data','testing')->render();
 *      - or -
 *      $app['blade']->template('test')->with('data','testing')->render();
 *
 * @see `view()` function.
 */
class Blade
{
    /** @var BladeConfigurationSet */
    protected $context;

    /** @var string - the template name */
    protected $template;

    /**
     * Blade constructor.
     *
     * @param BladeViewConfigurationInterface $context
     */
    public function __construct(BladeViewConfigurationInterface $context)
    {
        $this->context = $context;
        $this->template = 'default';
    }

    /**
     * @return BladeConfigurationSet|BladeViewConfigurationInterface
     */
    public function getContext() : BladeViewConfigurationInterface
    {
        return $this->context;
    }

    /**
     * @param mixed $items
     *
     * @return $this|Blade
     */
    public function merge($items) : Blade
    {
        $this->context->merge($items);

        return $this;
    }

    /**
     * @param string $view
     * @param array  $data
     *
     * @return string
     */
    public function render($view = NULL, array $data = []) : string
    {
        // merge existing scope with provided data array
        $this->merge($data);

        if (NULL === $view) {
            $view = $this->template;
        }

        // one way or the other, there must be a template to render
        if (NULL === $view) {
            throw new \InvalidArgumentException('No view template supplied.');
        }

        $path = $this->context->finder()->find($view);
        $blade_view = new View($this->context->factory(), $this->context->engine(), NULL, $path, $this->context->toArray());

        return $blade_view->render();

    }

    /**
     * @param string $view
     * @param array  $data
     * @param int    $code    - default 200 (OK)
     * @param array  $headers - default none
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response($view = NULL, array $data = [], $code = 200, array $headers = []) : Response
    {
        return response($this->render($view, $data), $code, $headers);
    }

    /**
     * @param string $view
     *
     * @return Blade
     */
    public function template($view = 'default') : Blade
    {
        $this->template = $view;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return Blade|static
     */
    public function with($name, $value) : Blade
    {
        $this->context[$name] = $value;

        return $this;
    }
}
