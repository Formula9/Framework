<?php namespace F9\Support\Provider;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Pimple\Container;
use Silex\Provider\RememberMeServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(Container $app)
    {
        $this->app->bootProvider(SecurityServiceProvider::class);
    }

    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app->register(new SecurityServiceProvider, $this->config['auth']);

        $app['security.encoder.digest'] = function () use ($app) {
            return new UserPasswordEncoder($app['security.encoder_factory']);
        };

        $app['security.utils'] = function ($app) {
            return new AuthenticationUtils($app['request_stack']);
        };

        $app->register(new RememberMeServiceProvider);
        // note: use `formula nine:password <password>` command to generate a password hash.
    }
}
