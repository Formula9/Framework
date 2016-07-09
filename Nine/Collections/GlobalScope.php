<?php namespace Nine\Collections;

/**
 * Global Scope is a specific form of the Scope class that stores global settings
 * and values. This is used primarily by rendering classes, but may carry other
 * application generic information.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Contracts\FactoryInterface;

class GlobalScope extends Scope
{
    /**
     * GlobalScope constructor.
     *
     * @param FactoryInterface $factory Requires the AppFactory for access to the environment.
     */
    public function __construct(FactoryInterface $factory)
    {
        parent::__construct($factory::getEnvironment());
    }
}
