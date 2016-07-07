<?php namespace F9\Contracts;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Database\DatabaseInterface;

/**
 * Base Database Event
 */
interface DatabaseEventInterface
{
    /**
     * DatabaseEvent constructor.
     *
     * @param DatabaseInterface $database
     * @param array             $payload
     * @param string            $context
     * @param bool              $halt
     */
    public function __construct(DatabaseInterface $database, array $payload = [], string $context = 'global.event', bool $halt = FALSE);

    /**
     * @return string
     */
    public function getContext() : string;

    /**
     * @return DatabaseEventInterface
     */
    public function getDatabase();

}
