<?php namespace F9\Support\Provider;

use Nine\Exceptions\IncompleteFormServiceRequirement;
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
        if (class_exists('Symfony\Component\Config\ConfigCache') and class_exists('Symfony\Component\Form\Form')) {

            $app->register(new CsrfServiceProvider);
            $app->register(new SilexFormServiceProvider);
            $app->register(new ValidatorServiceProvider);

            return;
        }

        throw new IncompleteFormServiceRequirement(
            'You must install the `symfony/config` and `symfony/form` packages to use Core Form Service Providers.');
    }
}
