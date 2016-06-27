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
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Symfony Routing component Provider for URL generation.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class UrlGeneratorServiceProvider implements ServiceProviderInterface
{
    public function boot(Container $app)
    {
    }

    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        /** @noinspection OnlyWritesOnParameterInspection
         * @param $app
         *
         * @return UrlGenerator
         */
        $app['url_generator'] = function ($container) {

            return new UrlGenerator($container['routes'], $container['request_context']);
        };
    }
}
