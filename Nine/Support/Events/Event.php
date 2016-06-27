<?php namespace Nine\Events;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Event extends SymfonyEvent
{
    /** @var bool */
    protected $halt;

    /** @var array */
    protected $payload;

    /**
     * Event constructor.
     *
     * @param array|NULL $payload
     * @param bool       $halt
     */
    public function __construct(array $payload = NULL, $halt = FALSE)
    {
        $this->payload = $payload;
        $this->halt = $halt;
    }

    /**
     * @return array
     */
    public function getPayload() : array
    {
        return $this->payload;
    }

    /**
     * @return boolean
     */
    public function isHalt() : bool
    {
        return $this->halt;
    }
}
