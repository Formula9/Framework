<?php namespace Nine;

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
 */

use Nine\Containers\ContainerAbstractNotFound;
use Nine\Containers\ContainerInterface;
use Nine\Library\Lib;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * JAR - Just Another Reflector.
 *
 * **Reflector provides methods for invoking and collecting dependencies for object
 * methods, functions and Closures.**
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
final class Reflector
{

    /** @var ContainerInterface */
    protected $container;

    /** @var Request */
    protected $current_request;

    /**
     * Reflector constructor.
     *
     * @param ContainerInterface $container
     * @param Request            $request
     */
    public function __construct(ContainerInterface $container, Request $request)
    {
        $this->current_request = $request;
        $this->container = $container;
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
    public function getControllerArguments(Request $request, $controller) : array
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

        // collect and return the list of required parameters
        return $this->getMethodArguments($request, $controller, $r->getParameters());
    }

    /**
     * **Discovers and returns method arguments using dependency injection.**
     *
     * The request must either be in the handler chain, or populated via
     * $app->runRequest($request) or decorate_request().
     *
     * @param Request $request
     * @param         $controller
     * @param array   $parameters
     *
     * @return array
     */
    public function getMethodArguments(Request $request, $controller, array $parameters) : array
    {
        // use this request
        $this->current_request = $request;

        // collect the current request attributes
        $attributes = $this->current_request->attributes->all();

        // locate method argument dependencies
        $dependencies = $this->extract_dependencies($controller);

        // get the list of argument values. ie: the `User` in `method(User $users)`
        $inject = $this->collect_dependencies($parameters, $dependencies['arg_list']);

        // match dependencies with parameters and collect argument list.
        $arguments = [];
        foreach ($parameters as $param) {

            /** @var \ReflectionParameter $param */

            // first, collect any resolved dependencies
            if (array_key_exists($param->name, $inject)) {
                $arguments[] = $inject[$param->name];

                continue;
            }

            // next, look for a dependency in the attributes
            if (array_key_exists($param->name, $attributes)) {
                /** @noinspection NotOptimalIfConditionsInspection */
                if (PHP_VERSION_ID >= 50600 && $param->isVariadic() && is_array($attributes[$param->name])) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $arguments = array_merge($arguments, array_values($attributes[$param->name]));
                }
                else {
                    $arguments[] = $attributes[$param->name];
                }

                continue;
            }

            // then handle a Request argument
            if ($param->getClass() && $param->getClass()->isInstance($request)) {
                $arguments[] = $request;

                continue;
            }

            // grab a default value, if it exists. ie: method($range = [1,2,3])
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();

                continue;
            }

            // fall through to an error
            $this->bad_controller_argument($controller, $param);
        }

        return $arguments;
    }

    /**
     * @param $class
     * @param $method
     *
     * @return \ReflectionFunction|\ReflectionMethod
     */
    public function getReflection($class, $method = NULL)
    {
        $reflection = NULL;

        switch ($class) {

            // if no method supplied and $class is an array then assume:
            //      `[class ,method]`
            //
            case ! $method:
                # if a callable is supplied then treat it as a function
                if ($class instanceof \Closure) {
                    $reflection = new \ReflectionFunction($class);

                    break;
                }

                if (is_array($class)) {
                    // extract the class and method
                    list($controller, $method) = $class;
                    $reflection = new \ReflectionMethod($controller, $method);

                    break;
                }

                # if a callable is supplied then treat it as a function
                if (is_callable($class)) {
                    $reflection = new \ReflectionFunction($class);

                    break;
                }

                break;

            // if a method is supplied then reflect on the class and method.
            case $method:

                $reflection = new \ReflectionMethod($class, $method);
                break;

            # anything but a closer
            default :

                $reflection = new \ReflectionMethod($class, $method);
                break;
        }

        return $reflection;
    }

    /**
     * **Invokes a class method with dependency injection.**
     *
     * Creates and optionally invokes a class->method() using dependency injection.
     *
     * @param string|object $class   - name of the class to make
     * @param string        $method  - class method to call
     * @param bool          $execute - TRUE to execute the object->method, FALSE instantiates and returns
     *
     * @return mixed - whatever the object returns
     */
    public function invokeClassMethod($class, $method = NULL, $execute = TRUE)
    {
        // is the class described by `class@method`?
        if (is_string($class) and Lib::str_has(':', $class)) {
            list($class, $method) = explode(':', $class);
        }

        // extract the route class dependencies
        $class_dependencies = $this->extract_dependencies($class, $method);
        list($reflection, $arguments) = array_values($class_dependencies);

        // construct a new class object.
        // this will trigger an early return
        if ($method === '__construct') {

            /** @var ReflectionClass $reflection */
            $reflection = new \ReflectionClass($class);

            // this is a simple call to instantiate a class.
            return $reflection->newInstanceArgs($arguments);
        }

        // optionally, transfer control over to the class:method.
        if ($execute) {

            /** @var \ReflectionClass $rf */
            $rf = new \ReflectionClass($reflection->class);

            $constructor = $class;

            // determine if a constructor exists and has required parameters
            if ($rf->getConstructor()) {
                // extract its dependencies
                $dependencies = $this->extract_dependencies($class, '__construct');
                // instantiate the object through its constructor
                $constructor = $rf->newInstanceArgs($dependencies['arg_list']);
            }

            // invoke the method
            /** @var \ReflectionMethod $reflection */
            return $reflection->invokeArgs($constructor, $arguments);
        }

        # no constructor so instantiate the class without it.
        return new $reflection->class;
    }

    /**
     * @param $controller
     * @param $param
     *
     * @throws \RuntimeException
     */
    protected function bad_controller_argument($controller, $param)
    {
        switch ($controller) {

            case is_array($controller):
                $message = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
                break;

            case is_object($controller):
                $message = get_class($controller);
                break;

            default :
                $message = $controller;
                break;
        }

        throw new RuntimeException(
            sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).',
                $message,
                $param->name)
        );
    }

    /**
     * @param array $parameters
     * @param       $arg_list
     *
     * @return array
     */
    protected function collect_dependencies(array $parameters, $arg_list) : array
    {
        // collect dependencies to inject.
        $inject = [];

        // pair and collect parameters by name with value.
        array_map(function ($parameter, $instance) use (&$inject) {

            $inject[$parameter->name] = $instance;

        }, $parameters, $arg_list);

        return $inject;
    }

    /**
     * Extract Dependencies.
     *
     * @param string|array|object|callable $class  - name of the class to make
     * @param string                       $method - class method to call
     *
     * @return callable|string
     * @throws ContainerAbstractNotFound
     */
    protected function extract_dependencies($class, $method = NULL)
    {
        // collect the request attributes
        $attributes = $this->current_request->attributes->all();

        // this will return an empty array if none are found
        $route_parameters = $this->route_parameters();

        // create a reflection based on the format of `$class` and `$method`.
        $reflection = $this->getReflection($class, $method);

        # locate the method arguments
        $arguments = $reflection->getParameters();

        # build an argument list to pass to the closure/method
        # this will contain instantiated dependencies.
        # more or less
        $arg_list = [];

        foreach ($arguments as $key => $arg) {

            # determine and retrieve the class of the argument, if it exists.
            $dependency_class = ($arg->getClass() === NULL) ? NULL : $arg->getClass()->name;

            # found in the container?
            if ($this->container->has($dependency_class)) {
                $arg_list[] = $this->container[$dependency_class]; # Forge::get($dependency_class);

                continue;
            }

            # use the default value if it exists
            if ($arg->isDefaultValueAvailable()) {
                $arg_list[] = $arg->getDefaultValue();

                continue;
            }

            # how about a valid class name?
            if (class_exists($dependency_class)) {

                # a class exists as an argument but was not found in the Forge,
                # so instantiate the class with dependencies
                $arg_list[] = $this->invokeClassMethod($dependency_class, '__construct'); # new $dependency_class;

                // next
                continue;
            }

            # we didn't find it in the containers, so look in the request
            if (array_key_exists($arg->name, $route_parameters)) {

                $arg_list[] = $route_parameters[$arg->name];

                continue;
            }

            # throw if no argument reference was found.
            throw new ContainerAbstractNotFound(
                $arg->name,
                'The ' . class_basename($this) . " was unable to resolve a dependency for: ($dependency_class \$$arg->name )",
                E_USER_ERROR);
        }

        # return an array containing the reflection and the list of qualified argument instances
        return compact('reflection', 'arg_list');
    }

    /**
     * @return array
     */
    protected function route_parameters()
    {
        # collect any route parameters
        $parameters = $this->current_request->attributes->all();

        return array_key_exists('_route_params', $parameters) ? $parameters['_route_params'] : [];
    }

}
