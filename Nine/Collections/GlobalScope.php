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

use Nine\Collections\Interfaces\EnvironmentInterface;

class GlobalScope extends Scope
{
    /**
     * GlobalScope constructor.
     *
     * @param EnvironmentInterface $environment Requires the AppFactory for access to the environment.
     */
    public function __construct(EnvironmentInterface $environment = NULL)
    {
        parent::__construct($environment
            ? $environment->get('*')
            : [
                'developing' => env('APP_ENV', 'PRODUCTION') !== 'PRODUCTION',
                'app_key'    => env('APP_KEY', '$invalid$this&key%must#be@changed'),
                'debugging'  => env('DEBUG', FALSE),
                'testing'    => env('TESTING', FALSE),
            ]);
    }
}
