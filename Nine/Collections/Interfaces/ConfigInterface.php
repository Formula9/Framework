<?php namespace Nine\Collections;

/**
 * Config is a general purpose configuration store and injector.
 *
 * Supports use of dot-notation keys.
 *
 * @package Nine Collections
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
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
     * @param string $basePath
     * @param string $mask
     *
     * @return static
     */
    public function importFolder($basePath, $mask = '*.php');

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

}
