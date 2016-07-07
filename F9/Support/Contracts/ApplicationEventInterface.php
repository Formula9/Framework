<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
namespace F9\Contracts;

use F9\Application\Application;

/**
 * Base Application Event
 */
interface ApplicationEventInterface
{
    public function __construct(Application $app, array $payload = [], $halt = FALSE);

    public function getApplication() : Application;
}
