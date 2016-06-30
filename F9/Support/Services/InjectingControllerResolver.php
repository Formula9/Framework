<?php namespace F9\Support\Services;

/**
 * F9 (Formula 9) Personal PHP Framework
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
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Containers\ContainerInterface;
use Nine\Containers\Forge;
use Nine\Reflector;
use Pimple\Container;
use Pimple\ServiceProviderInterface as PimpleServiceProviderInterface;
use Silex\CallbackResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * **The `InjectingControllerResolver` is a modified version of the
 * `ServiceControllerResolver` provided with Silex, and provides a
 * method for injecting controller services directly from the
 * app controller folder (as defined by the `CONTROLLERS` constant.**
 *
 * Also, this resolver imports additional (possibly) out of
 * namespace controllers. See `config('app.namespaced_controllers')`.
 */
class InjectingControllerResolver implements ControllerResolverInterface, PimpleServiceProviderInterface
{
    /** @var CallbackResolver */
    protected $callbackResolver;

    /** @var Forge $container */
    protected $container;

    /** @var ControllerResolverInterface */
    protected $controllerResolver;

    /** @var Request */
    protected $current_request;

    /** @var ContainerInterface */
    protected $injector;

    /**
     * The ControllerResolver Constructor.
     *
     * @param ContainerInterface          $container
     * @param Reflector                   $injector
     * @param ControllerResolverInterface $controllerResolver A ControllerResolverInterface instance to delegate to
     * @param CallbackResolver            $callbackResolver   A service resolver instance
     */
    public function __construct(
        ContainerInterface $container,
        Reflector $injector,
        ControllerResolverInterface $controllerResolver,
        CallbackResolver $callbackResolver)
    {
        $this->container = $container;
        $this->injector = $injector;
        $this->controllerResolver = $controllerResolver;
        $this->callbackResolver = $callbackResolver;
    }

    /**
     * **Returns the arguments to pass to the controller.**
     *
     * This is the normal entry point -- called by the `HttpKernel` when the
     * `Application` `run` or `request` methods are called.
     *
     * @param Request  $request
     * @param callable $controller
     *
     * @return array An array of arguments to pass to the controller
     */
    public function getArguments(Request $request, $controller) : array
    {
        // store the current request for local reference.
        $this->current_request = $request;

        // if the controller is an array then assume the controller is [<controller_class>, <method>]
        if (is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
        }

        // --or-- if an instantiated controller object which is not a Closure then
        //      assume the `__invoke` method
        elseif (is_object($controller) && ! $controller instanceof \Closure) {
            $r = new \ReflectionObject((object) $controller);
            $r = $r->getMethod('__invoke');
        }

        // --finally-- assume the controller is a `callable` entity
        else {
            $r = new \ReflectionFunction($controller);
        }

        // collect and return the list of constructor|controller:method parameters
        return $this->injector->getMethodArguments($request, $controller, $r->getParameters());
    }

    /**
     * **Return the Controller instance associated with a Request.**
     *
     * As several resolvers can exist for a single application, a resolver must
     * return false when it is not able to determine the controller.
     *
     * The resolver must only throw an exception when it should be able to load
     * controller but cannot because of some errors made by the developer.
     *
     * @param Request $request A Request instance
     *
     * @return callable|false A PHP callable representing the Controller,
     *                        or false if this resolver is not able to determine the controller.
     */
    public function getController(Request $request) : callable
    {
        $controller = $request->attributes->get('_controller', NULL);

        if ( ! $this->callbackResolver->isValid($controller)) {
            return $this->controllerResolver->getController($request);
        }

        return $this->callbackResolver->convertCallback($controller);
    }

    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['invoke_with_dependencies'] = $app->protect(function ($class, $method, $execute = NULL) {
            return $this->injector->invokeClassMethod($class, $method, TRUE);
        });
    }

}
