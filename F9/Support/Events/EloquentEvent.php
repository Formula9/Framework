<?php namespace F9\Events;

use Illuminate\Database\DatabaseManager;
use Nine\Events\Event;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class EloquentEvent extends Event
{
    public function __construct(
        DatabaseManager $database,
        array $payload = [],
        string $context = 'global.event',
        bool $halt = FALSE)
    {
        parent::__construct($payload, $halt);
    }
}
