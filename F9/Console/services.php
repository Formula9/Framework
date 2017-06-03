<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

return [
    //
    // Service Providers registered for console commands.
    //
    'providers' =>
        [
            /* REGISTER FIRST */

            // required for illuminate-based services
            F9\Support\Provider\IlluminateServiceProvider::class,
            // register early for access to debug functions
            F9\Support\Provider\TracyServiceProvider::class,
            // register for logging and reporting
            F9\Support\Provider\ReportingServiceProvider::class,
            // establish routing early
            //F9\Support\Provider\RoutingServiceProvider::class,

            /* REGISTER NEXT */

            F9\Support\Provider\DatabaseServiceProvider::class,

            /* REGISTER LAST */

        ],
];
