<?php namespace App\Events;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Application\Application;
use Nine\Events\Event;

/**
 * Base Application Event
 */
class ApplicationEvent extends Event
{
    /** @var Application */
    private $app;

    public function __construct(Application $app, array $payload = [], $halt = FALSE)
    {
        $this->app = $app;

        parent::__construct($payload, $halt);
    }

    public function getApp()
    {
        return $this->app;
    }

}
