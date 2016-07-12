<?php namespace F9\Container\Contracts;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface SymbolStorageInterface
{
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
    public function retrieve(string $abstract, $default);

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
    public function store(string $abstract, $value);
}
