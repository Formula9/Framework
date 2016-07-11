<?php namespace F9\Container;

/**
 * @package Research Container
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Auryn\InjectionException;
use Auryn\Injector;
use F9\Container\Contracts\ContainerContract;
use F9\Container\Exceptions\PotionContainerInvalidContextException;
use F9\Exceptions\CannotAddNonexistentClass;

class Potion implements ContainerContract, \ArrayAccess
{
    /**
     * @var array
     */
    private $definitions;

    /**
     * @var Injector
     */
    private $injector;

    /**
     * Potion constructor.
     *
     * Potion encapsulates the Auryn Dependency Injector.
     *
     * use:
     *      $potion = new Potion(new Injector(NULL, new Reflector|StandardReflector|CachingReflector))
     *
     * sample definitions array:
     *
     *  [
     *      'main' => [
     *          'alias'  => [Config::class, TestSettings::class],
     *          'define' => [
     *              [Config::class, [':items' => Config::createFromFolder(\CONFIG)]],
     *              [Connections::class, [':config' => Config::createFromFolder(\CONFIG)['database']]],
     *              [PDO::class, [':dsn' => 'mysql:dbname=test;host=127.0.0.1', ':username' => 'username', ':passwd' => 'password']],
     *              [Server::class, [':server' => $_SERVER]],
     *          ],
     *          'share'  => ['PDO', Connections::class, Config::class],
     *      ],
     *  ]
     *
     * @param Injector $injector    - Injector or NULL
     * @param array    $definitions - a correctly formatted array of definitions
     */
    public function __construct(Injector $injector, array $definitions = NULL)
    {
        $this->injector = $injector;
        $this->definitions = $definitions;
        empty($definitions) ?: $this->register($definitions);
    }

    /**
     * Add (bind) an to an implementation, with optional alias.
     *
     *  Notes:<br>
     *      - `$abstract` is either `['<abstract>', '<alias>']`, `['<abstract>']` or `'<abstract>'`.<br>
     *      - `$concrete` objects that are not *anonymous functions* are added as **instances**.<br>
     *      - All other cases result in binding.<br>
     *    <br>
     *  *Order is important*.<br>
     *      - Correct: `add([Thing::class, 'thing'], ...)`<br>
     *      - Incorrect: `add(['thing', Thing::class], ...)`<br>
     *    <br>
     *
     * @param string|string[] $abstract
     * @param mixed           $concrete
     *
     * @throws CannotAddNonexistentClass
     */
    public function add($abstract, $concrete = NULL)
    {
        // TODO: Implement add() method.
    }

    /**
     * @param $original
     * @param $alias
     *
     * @return $this
     * @throws \Auryn\ConfigException
     */
    public function alias($original, $alias)
    {
        $this->injector->alias($original, $alias);

        return $this;
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string $callback
     * @param  array           $parameters
     * @param  string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = NULL)
    {
        return $this->injector->execute($callback, $parameters);
    }

    /**
     * Define instantiation directives for the specified class
     *
     * @param string $name The class (or alias) whose constructor arguments we wish to define
     * @param array  $args An array mapping parameter names to values/instructions
     *
     * @return self
     */
    public function define($name, array $args)
    {
        $this->injector->define($name, $args);

        return $this;
    }

    /**
     * Assign a global default value for all parameters named $paramName
     *
     * Global parameter definitions are only used for parameters with no typehint, pre-defined or
     * call-time definition.
     *
     * @param string $paramName The parameter name for which this value applies
     * @param mixed  $value     The value to inject for this parameter name
     *
     * @return self
     */
    public function defineParam($paramName, $value)
    {
        $this->injector->defineParam($paramName, $value);

        return $this;
    }

    /**
     * @param string $name
     * @param        $callableOrMethodStr
     *
     * @return $this
     * @throws \Auryn\ConfigException
     */
    public function delegate(string $name, $callableOrMethodStr)
    {
        $this->injector->delegate($name, $callableOrMethodStr);

        return $this;
    }

    /**
     * Register a prepare callable to modify/prepare objects of type $name after instantiation
     *
     * Any callable or provisionable invokable may be specified. Preparers are passed two
     * arguments: the instantiated object to be mutated and the current Injector instance.
     *
     * @param string $name
     * @param mixed  $callableOrMethodStr Any callable or provisionable invokable method
     *
     * @throws InjectionException if $callableOrMethodStr is not a callable.
     *                            See https://github.com/rdlowrey/auryn#injecting-for-execution
     * @return self
     */
    public function extend($name, $callableOrMethodStr)
    {
        $this->injector->prepare($name, $callableOrMethodStr);

        return $this;
    }

    /**
     * @return Injector
     */
    public function getInjector()
    {
        return $this->injector;
    }

    /**
     * **Report whether an exists in the $this or the Application container.**
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function has($abstract) : bool
    {
        return isset($this->injector[$abstract]);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string|array $abstract
     * @param  mixed        $instance
     *
     * @return $this
     */
    public function instance($abstract, $instance)
    {
        $this->injector->define($abstract, (array) $instance);

        return $this;
    }

    /**
     * **Finds an entry of the container by its identifier and returns it.**
     *
     * @param string $abstract Identifier of the entry to look for.
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->injector->make($abstract, $parameters);
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     *
     * @return Injector
     */
    public function offsetGet($offset)
    {
        return $this->injector->make($offset);
    }

    /**
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->injector->define($offset, (array) $value);
    }

    /**
     * @param string $offset
     *
     * @throws PotionContainerInvalidContextException
     */
    public function offsetUnset($offset)
    {
        try {
            unset($this->injector[$offset]);
        } catch (\Exception $e) {
            throw new PotionContainerInvalidContextException($e->getMessage() . "\nThe container does not allow for forgetting definitions.");
        }
    }

    /**
     * Parse a configuration array into Potion entries.
     *
     * structure: ['key' => ['share|define|define.param|add' => [$key=>$value|$value],]
     *
     * @param $array
     */
    public function register($array)
    {
        foreach ((array) $array as $item => $definition) {
            $this->importDefinition($definition);
        }
    }

    public function share($concrete)
    {
        $this->injector->share($concrete);

        return $this;
    }

    /**
     * @param array|string $abstract
     * @param null         $concrete
     */
    public function singleton($abstract, $concrete = NULL)
    {
        $this->injector->define($abstract, (array) $concrete);
        $this->injector->share($concrete);
    }

    /**
     * @param $configurationType
     * @param $definition
     */
    private function importConfiguration($configurationType, $definition)
    {
        switch ($configurationType) {
            case 'add':
                $this->add($definition[0], $definition[1]);
                break;

            case 'alias':
                $this->registerAliases($definition);
                break;

            case 'define':
                $this->registerDefines($definition);
                break;

            case 'define.param':
                $this->registerDefinedParameters($definition);
                break;

            case 'delegate':
                $this->registerDelegates($definition);
                break;

            case 'share':
                $this->registerShares($definition);
                break;

            default :
                break;
        }
    }

    /**
     * @param $definition
     */
    private function importDefinition(array $definition)
    {
        foreach ($definition as $type => $define) {
            $this->importConfiguration($type, $define);
        }
    }

    private function registerAliases(array $aliases)
    {
        foreach ((array) $aliases as $entry) {
            $this->alias($entry[0], $entry[1]);
        }
    }

    private function registerDefinedParameters(array $parameters)
    {
        foreach ((array) $parameters as $entry) {
            $this->defineParam($entry[0], $entry[1]);
        }
    }

    private function registerDefines(array $defines)
    {
        foreach ((array) $defines as $entry) {
            $this->define($entry[0], $entry[1]);
        }
    }

    private function registerDelegates($delegates)
    {
        foreach ((array) $delegates as $entry) {
            $this->delegate($entry[0], $entry[1]);
        }
    }

    private function registerShares(array $shares)
    {
        foreach ((array) $shares as $entry) {
            $this->share($entry);
        }
    }
}
