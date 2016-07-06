<?php namespace F9\Events;

use F9\Contracts\EloquentEventInterface;
use Illuminate\Database\DatabaseManager;
use Nine\Events\Event;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class EloquentEvent extends Event implements EloquentEventInterface
{
    /**
     * @var string
     */
    private $context;

    /**
     * @var DatabaseManager
     */
    private $database;

    public function __construct(
        DatabaseManager $database,
        array $payload = [],
        string $context = 'global.event',
        bool $halt = FALSE)
    {
        parent::__construct($payload, $halt);

        $this->database = $database;
        $this->context = $context;
        $this->halt = $halt;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return DatabaseManager
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

    }

}
