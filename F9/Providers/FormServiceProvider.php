<?php namespace F9\Support\Provider;

use Pimple\Container;
use Silex\Provider\CsrfServiceProvider;
use Silex\Provider\FormServiceProvider as SilexFormServiceProvider;
use Silex\Provider\ValidatorServiceProvider;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class FormServiceProvider extends ServiceProvider
{

    public function register(Container $app)
    {
        $app->register(new CsrfServiceProvider);
        $app->register(new SilexFormServiceProvider);
        $app->register(new ValidatorServiceProvider);
    }
}
