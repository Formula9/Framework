<?php namespace F9\Support\Provider;

use Pimple\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class FormServiceProvider extends ServiceProvider
{

    public function register(Container $app)
    {
        $app->register(new \Silex\Provider\CsrfServiceProvider);
        $app->register(new \Silex\Provider\FormServiceProvider);
        $app->register(new \Silex\Provider\ValidatorServiceProvider);
    }
}
