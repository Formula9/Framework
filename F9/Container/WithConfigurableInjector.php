<?php namespace F9\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

trait WithConfigurableInjector
{
    /**
     * @var array
     */
    private $definitions;

    /**
     * Parse a configuration array into Potion entries.
     *
     * structure: ['key' => ['share|define|define.param|add' => [$key=>$value|$value],]
     *
     * @param $array
     *
     * @return $this
     */
    public function register($array)
    {
        foreach ((array) $array as $item => $definition) {
            $this->importItemDefinition($item, $definition);
        }

        return $this;
    }

    /**
     * @param $configurationType
     * @param $definition
     */
    private function importDeclaration($configurationType, $definition)
    {
        switch ($configurationType) {
            case 'add':
                $this->registerAdds($definition);
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

            case 'extend':
                $this->registerExtensions($definition);
                break;

            case 'share':
                $this->registerShare($definition);
                break;

            default :
                break;
        }
    }

    /**
     * @param string $item
     * @param array  $definition
     */
    private function importItemDefinition(string $item, array $definition)
    {
        foreach ($definition as $type => $define) {
            $this->importDeclaration($item, $define);
        }
    }

    /**
     * @param $definitions
     */
    private function registerAdds($definitions)
    {
        foreach ((array) $definitions as $class => $define) {
            $this->add($class, $define);
        }
    }

    private function registerAliases(array $aliases)
    {
        foreach ((array) $aliases as $original => $alias) {
            $this->alias($original, $alias);
        }
    }

    private function registerDefinedParameters(array $parameters)
    {
        foreach ((array) $parameters as $paramName => $value) {
            $this->defineParam($paramName, $value);
        }
    }

    private function registerDefines(array $defines)
    {
        foreach ((array) $defines as $name => $args) {
            $this->define($name, $args);
        }
    }

    private function registerDelegates($delegates)
    {
        foreach ((array) $delegates as $name => $callableOrMethodStr) {
            $this->delegate($name, $callableOrMethodStr);
        }
    }

    /**
     * @param $definitions
     */
    private function registerExtensions($definitions)
    {
        foreach ((array) $definitions as $name => $callableOrMethodStr) {
            $this->extend($name, $callableOrMethodStr);
        }
    }

    private function registerShare($class)
    {
        $this->share($class);
    }
}
