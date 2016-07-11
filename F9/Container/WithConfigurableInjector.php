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
     */
    public function register($array)
    {
        foreach ((array) $array as $item => $definition) {
            $this->importDefinition($definition);
        }
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
