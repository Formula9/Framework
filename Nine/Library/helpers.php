<?php

/**
 * Convenience Library functions.
 *
 * @package Nine Collections
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Library\Lib;

if (PHP_VERSION_ID < 70000) {
    echo('Formula 9 requires PHP versions >= 7.0.0');
    exit(1);
}

if (defined('LIBRARY_HELPERS_LOADED')) {
    return TRUE;
}

define('LIBRARY_HELPERS_LOADED', TRUE);

if ( ! function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     *
     * @param  string $value
     *
     * @return string
     */
    function e($value) : string
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', FALSE);
    }
}

if ( ! function_exists('elapsed_time_since_request')) {
    /**
     * @param bool $raw
     *
     * @return string | int
     */
    function elapsed_time_since_request($raw = FALSE)
    {
        return ! $raw
            ? sprintf('%8.1f ms', (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
            : (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    }
}

if ( ! function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    function env($key, $default = NULL)
    {
        $value = getenv($key);

        if ($value === FALSE) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return TRUE;

            case 'false':
            case '(false)':
                return FALSE;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return NULL;
        }

        if (strlen($value) > 1 && Lib::starts_with('"', $value) && Lib::ends_with('"', $value)) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if ( ! function_exists('value')) {
    /**
     *  Returns value of a variable. Resolves closures.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof \Closure || is_callable($value) ? $value() : $value;
    }
}

if ( ! function_exists('words')) {

    /**
     * Converts a string of space or tab delimited words to an array.
     * Multiple whitespace between words is converted to a single space.
     *
     * ie:
     *      words('one two three') -> ['one','two','three']
     *      words('one:two',':') -> ['one','two']
     *
     *
     * @param string $words
     * @param string $delimiter
     *
     * @return array
     */
    function words($words, $delimiter = ' ') : array
    {
        return explode($delimiter, preg_replace('/\s+/', ' ', $words));
    }
}

if ( ! function_exists('tuples')) {

    /**
     * Converts an encoded string to an associative array.
     *
     * ie:
     *      tuples('one:1, two:2, three:3') -> ["one" => 1,"two" => 2,"three" => 3,]
     *
     * @param $encoded_string
     *
     * @return array
     */
    function tuples($encoded_string) : array
    {
        $array = words($encoded_string, ',');
        $result = [];

        foreach ($array as $tuple) {
            $ra = explode(':', $tuple);

            $key = trim($ra[0]);
            $value = trim($ra[1]);

            $result[$key] = is_numeric($value) ? (int) $value : $value;
        }

        return $result;
    }
}
