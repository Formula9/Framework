<?php namespace F9\Container;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
trait WithSymbolStorage
{
    private $protectedSymbols;

    /** @var array $symbols - A symbol table of non-class instances */
    private $symbols = [];

    private $symbolsAllowed = FALSE;

    public function disableSymbols()
    {
        $this->symbolsAllowed = FALSE;
    }

    public function enableSymbols()
    {
        $this->symbolsAllowed = TRUE;
    }

    /**
     * Retrieves a symbol value from the symbol table.
     *
     * Note that symbols must not be used as service locators! The purpose
     * of storing symbols is to allow access to configuration values from with
     * the container.
     *
     * @param string $abstract
     * @param mixed  $default
     *
     * @return array
     */
    public function retrieveValue(string $abstract, $default = NULL)
    {
        if ( ! $this->symbolsAllowed) {
            throw new \InvalidArgumentException('The symbol table is disabled.');
        }

        return $this->locateSymbol($abstract, $default);
    }

    /**
     * Stores a symbol (configuration value) in the symbol table.
     *
     * Note that symbols must not be used as service locators! The purpose
     * of storing symbols is to allow access to configuration values from with
     * the container.
     *
     * Symbols cannot be instantiated by the container for the purpose of
     * dependency injection.
     *
     * @param string $abstract
     * @param        $value
     *
     * @return array
     */
    public function storeValue(string $abstract, $value)
    {
        if (empty($abstract)) {
            throw new \InvalidArgumentException('The name of the symbol cannot be blank.');
        }

        if ( ! $this->symbolsAllowed) {
            throw new \BadMethodCallException('The symbol table is disabled. Use enableSymbolTable() to use the symbol table.');
        }

        // resolve the instance into a subject value
        $subject = $this->resolveInstance($value);

        // now validate that the subject is not a class reference
        $this->validateParameters($abstract, $subject);

        // if callable then store it as a factory
        if (is_callable($value)) {
            $subject = $value;
        }

        $this->symbols[$abstract] = $subject;
    }

    /**
     * Locates a symbol in the symbol table.
     *
     * If not found then return the default.
     *
     * @param string $abstract
     * @param mixed  $default
     *
     * @return mixed
     */
    private function locateSymbol(string $abstract, $default = NULL)
    {
        if (isset($this->symbols[$abstract])) {

            $symbol = $this->symbols[$abstract];

            if (is_callable($symbol)) {
                return $symbol($this);
            }

            return $symbol;
        }

        return $default;
    }

    /**
     * Resolve and instance.
     *
     * If the instance is a callable use the value returned.
     *
     * @param mixed $instance
     *
     * @return mixed
     */
    private function resolveInstance($instance)
    {
        $value = $instance instanceof \Closure ? $instance() : $instance;
        $this->validateParameters('', $value);

        return $value;
    }

    /**
     * Validates that neither the abstract nor the value are classes.
     *
     * Classes must not be directly referenced by the symbol table.
     *
     * @param $abstract
     * @param $value
     */
    private function validateParameters($abstract, $value)
    {
        if (class_exists($abstract)) {
            throw new \InvalidArgumentException("Class abstracts ($abstract) are not allowed in the symbol table.");
        }

        if ((is_string($value) and class_exists($value)) or is_object($value)) {
            $className = is_string($value) ? $value : get_class($value);
            throw new \InvalidArgumentException("Class values ($className) are not allowed in the symbol table.");
        }
    }
}
