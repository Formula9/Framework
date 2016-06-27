<?php namespace Nine\Contracts;

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

/**
 * Config
 *
 * A general purpose configuration class with import/export methods
 * and \ArrayAccess with `dot` notation access methods.
 */
interface ConfigInterface
{
    /**
     * @param array $import
     */
    public function importArray(Array $import);

    /**
     * @param string $file
     */
    public function importFile($file);

    /**
     * Imports (merges) config files found in the specified directory.
     *
     * @param string $base_path
     * @param string $mask
     *
     * @return static
     */
    public function importFolder($base_path, $mask = '*.php');

    /**
     *
     * @param string $folder
     *
     * @return static
     */
    public static function createFromFolder($folder);

    /**
     * @param string $json - filename or JSON string
     *
     * @return static
     */
    public static function createFromJson($json);

    /**
     * @param $yaml
     *
     * @return static
     */
    public static function createFromYaml($yaml);

    /**
     * @param $abstract
     *
     * @return mixed
     */
    public static function setting($abstract);
}
