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

use Nine\Exceptions\CriticalApplicationFailure;
use Nine\Exceptions\ApplicationFailureAlert;
use Pimple\Container;
use Psr\Log\LoggerInterface;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Logger implements LoggerInterface
{
    /** @var Container */
    private $app;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Container $app, $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     * @throws ApplicationFailureAlert
     */
    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);

        throw new ApplicationFailureAlert($message);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     * @throws CriticalApplicationFailure
     */
    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);

        throw new CriticalApplicationFailure($message);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function emergency($message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null|void
     */
    public function log($level, $message, array $context = [])
    {
        // back trace to the origin of the log caller.
        $trace = getenv('DEBUG') ? ' [src] ' . $this->location_from_backtrace(3) : '';

        // otherwise, assuming there is any logger at all, pass using Monolog signature
        $this->logger ? $this->logger->log("[msg] $message$trace", $level, $context) : NULL;
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @param $index
     *
     * @return string
     */
    private function location_from_backtrace($index = 2) : string
    {
        $file = '';
        $line = 0;
        $dbt = debug_backtrace();

        if (array_key_exists($index, $dbt)) {

            if (array_key_exists('file', $dbt[$index])) {
                $file = basename($dbt[$index]['file']);
                $line = $dbt[$index]['line'];
            }

            $function = array_key_exists('function', $dbt[$index]) ? $dbt[$index]['function'] : 'λ()';
            $class = array_key_exists('class', $dbt[$index]) ? $dbt[$index]['class'] : '(λ)';

            return $file === '' ? "λ()~>$class::$function" : "$file:$line~>$class::$function";
        }

        return '[unidentifiable context]';
    }
}
