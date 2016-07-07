<?php namespace F9\Events;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Application\Application;
use F9\Contracts\ApplicationEventInterface;
use Nine\Events\Event;

/**
 * Base Application Event
 */
class ApplicationEvent extends Event implements ApplicationEventInterface
{
    /** @var Application */
    private $app;

    public function __construct(Application $app, array $payload = [], $halt = FALSE)
    {
        $this->app = $app;

        parent::__construct($payload, $halt);
    }

    public function getApplication() : Application
    {
        return $this->app;
    }

}
