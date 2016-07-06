<?php namespace F9\Events;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Contracts\DatabaseEventInterface;
use Nine\Database\DatabaseInterface;
use Nine\Events\Event;

/**
 * Base Database Event
 */
class DatabaseEvent extends Event implements DatabaseEventInterface
{
    /** @var mixed */
    protected $context;

    /** @var DatabaseInterface $database */
    protected $database;

    /**
     * DatabaseEvent constructor.
     *
     * @param DatabaseInterface $database
     * @param array             $payload
     * @param string            $context
     * @param bool              $halt
     */
    public function __construct(
        DatabaseInterface $database,
        array $payload = [],
        string $context = 'global.event',
        bool $halt = FALSE)
    {
        parent::__construct($payload, $halt);

        $this->database = $database;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getContext() : string
    {
        return $this->context;
    }

    /**
     * @return DatabaseInterface
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return array
     */
    public function getPayload() : array
    {
        return $this->payload;
    }

}
