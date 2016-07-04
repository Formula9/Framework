<?php

/**
 * F9 (Formula Nine) Personal PHP Framework
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

/**
 * Globally accessible convenience functions.
 *
 * @note    Please DO NOT USE THESE INDISCRIMINATELY!
 *       These functions (and those appended at the end)
 *       are intended mainly for views, testing and
 *       implementation hiding when temporarily useful.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Illuminate\Database\Eloquent\Model;
use Nine\Collections\Collection;
use Nine\Collections\Scope;
use Nine\Containers\Forge;
use Psr\Log\LoggerInterface;

if (PHP_VERSION_ID < 70000) {
    echo('Formula 9 requires PHP versions >= 7.0.0');
    exit(1);
}

// if this helpers file is included more than once, then calculate
// the global functions exposed and return a simple catalog.

if (defined('SUPPORT_HELPERS_LOADED')) {
    return TRUE;
}

define('SUPPORT_HELPERS_LOADED', TRUE);

if ( ! function_exists('array_except')) {
    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array|string $keys
     * @param  array        $array
     *
     * @return array
     */
    function array_except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }
}

if ( ! function_exists('collect')) {
    /**
     * Returns a collection containing the array values provided.
     *
     * @param array $array
     *
     * @return Collection
     */
    function collect(array $array)
    {
        return new Collection($array);
    }
}

if ( ! function_exists('applog')) {

    /**
     * Write an entry into a specific context log.
     *
     * Note that the written filename is "<local/logs/>$context.log".
     *
     * @param string $message
     * @param string $context
     */
    function applog($message, $context = 'info')
    {
        /** @var LoggerInterface $logger */
        static $logger;

        try {
            // try getting the framework logger
            $logger = $logger ?: forge('logger');

            // write the message
            $logger->log($context, $message);

        } catch (\InvalidArgumentException $e) {
            throw new \LogicException('applog(): no logger is available.');
        }

    }
}

if ( ! function_exists('dlog')) {
    /**
     * @param        $message
     * @param string $priority
     */
    function dlog($message, $priority = 'info')
    {
        if (env('DEBUG') and isset($app['logger'])) {
            app('logger')->{'log'}($priority, $message);
        }
    }
}

if ( ! function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     *
     * @param  string $value
     *
     * @return string
     */
    function e($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', FALSE);
    }
}

if ( ! function_exists('elapsed_time_since_request')) {
    /**
     * @param bool $raw
     *
     * @return string
     */
    function elapsed_time_since_request($raw = FALSE)
    {
        return ! $raw
            ? sprintf('%8.1f ms', (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
            : (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    }
}

/**
 * Kernel
 */
if ( ! function_exists('kernel')) {

    function kernel($property = NULL)
    {
        // object cache
        static $kernel = NULL;
        $kernel = $kernel ?: $kernel = forge('kernel');

        return NULL === $property ? $kernel : $kernel->{$property}();
    }
}

if ( ! function_exists('pad_left')) {

    /**
     * Left-pad a string
     *
     * @param string $str
     * @param int    $length
     * @param string $space
     *
     * @return string
     */
    function pad_left($str, $length = 0, $space = ' ')
    {
        return str_pad($str, $length, $space, STR_PAD_LEFT);
    }
}

if ( ! function_exists('pad_right')) {

    /**
     * Left-pad a string
     *
     * @param string $str
     * @param int    $length
     * @param string $space
     *
     * @return string
     */
    function pad_right($str, $length = 0, $space = ' ')
    {
        return str_pad($str, $length, $space, STR_PAD_RIGHT);
    }
}

if ( ! function_exists('memoize')) {
    /**
     * Cache repeated function results.
     *
     * @param $lambda - the function whose results we cache.
     *
     * @return Closure
     */
    function memoize($lambda)
    {
        return function () use ($lambda) {
            # results cache
            static $results = [];

            # collect arguments and serialize the key
            $args = func_get_args();
            $key = serialize($args);

            # if the key result is not cached then cache it
            if (empty($results[$key])) {
                $results[$key] = call_user_func_array($lambda, $args);
            }

            return $results[$key];
        };
    }
}

if ( ! function_exists('model_id')) {

    /**
     * returns the ID of a model or the value of the argument.
     *
     * @param Model|integer|callable $model
     *
     * @return integer
     */
    function model_id($model)
    {
        return $model instanceof Model ? $model->{'id'} : value($model);
    }
}

if ( ! function_exists('is_not')) {

    function is_not($subject)
    {
        return ! $subject;
    }
}

if ( ! function_exists('partial')) {
    /**
     * Curry a function.
     *
     * @param $lambda - the function to curry.
     * @param $arg    - the first or only argument
     *
     * @return Closure
     */
    function partial($lambda, $arg) : Closure
    {
        $func_args = func_get_args();
        $args = array_slice($func_args, 1);

        return function () use ($lambda, $args) {
            $full_args = array_merge($args, func_get_args());

            return call_user_func_array($lambda, $full_args);
        };
    }
}

if ( ! function_exists('scope')) {

    /**
     * Returns a reference to the global scope used primarily by views.
     *
     * @return Scope
     */
    function scope() : Scope
    {
        static $gs;
        $gs = $gs ?: Forge::find('global.scope');

        return $gs;
    }
}

if ( ! function_exists('share')) {

    /**
     * Merges data with the global scope, used by Views.
     *
     * @param $data
     */
    function share($data)
    {
        static $gs;
        $gs = $gs ?: scope();

        $gs->merge($data);
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
        return $value instanceof Closure ? $value() : $value;
    }
}

if ( ! function_exists('throw_now')) {

    /**
     * @param $exception
     * @param $message
     */
    function throw_now($exception, $message)
    {
        throw new $exception($message);
    }
}

if ( ! function_exists('throw_if')) {
    /**
     * @param string  $exception
     * @param string  $message
     * @param boolean $if
     */
    function throw_if($if, $exception, $message)
    {
        if ($if) {
            throw new $exception($message);
        }
    }
}

if ( ! function_exists('throw_if_not')) {
    /**
     * @param string  $exception
     * @param string  $message
     * @param boolean $if
     */
    function throw_if_not($if, $exception, $message)
    {
        if ( ! $if) {
            throw new $exception($message);
        }
    }
}

if ( ! function_exists('stopwatch')) {

    function stopwatch($event_name = NULL)
    {
        return $event_name ? app('stop.watch')->{'getEvent'}($event_name) : app('stop.watch');
    }
}

if ( ! function_exists('tail')) {
    // blatantly stolen from Ionuț G. Stan on stack overflow
    function tail($filename) : string
    {
        $line = '';

        $f = fopen(realpath($filename), 'r');
        $cursor = -1;

        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);

        /**
         * Trim trailing newline chars of the file
         */
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        /**
         * Read until the start of file or first newline char
         */
        while ($char !== FALSE && $char !== "\n" && $char !== "\r") {
            /**
             * Prepend the new char
             */
            $line = $char . $line;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        return $line;
    }
}

if ( ! function_exists('dd')) {

    /**
     * Override Illuminate dd()
     *
     * @param null $value
     * @param int  $depth
     */
    function dd($value = NULL, $depth = 8)
    {
        ddump($value, $depth);
    }
}

if ( ! function_exists('w')) {

    /**
     * Converts a string of space or tab delimited words as an array.
     * Multiple whitespace between words is converted to a single space.
     *
     * ie:
     *      w('one two three') -> ['one','two','three']
     *      w('one:two',':') -> ['one','two']
     *
     *
     * @param string $words
     * @param string $delimiter
     *
     * @return array
     */
    function w($words, $delimiter = ' ') : array
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
        $array = w($encoded_string, ',');
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
