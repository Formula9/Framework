<?php namespace F9\Events;

/**
 * F9 (Formula 9) Personal PHP Framework
 *
 * Copyright (c) 2010-2016, Greg Truesdell (<odd.greg@gmail.com>)
 * License: MIT (reference: https://opensource.org/licenses/MIT)
 *
 * Acknowledgements:
 *  - The code provided in this file (and in the Framework in general) may include
 * open sourced software licensed for the purpose, refactored code from related
 * packages, or snippets/methods found on sites throughout the internet.
 *  - All originator copyrights remain in force where applicable, as well as their
 *  licenses where obtainable.
 *
 * @package F9
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

final class NineEvents
{
    /**
     * The NOTIFY_APPLICATION_SHUTDOWN event occurs after the application
     * has completed, and before PHP handles termination activities.
     *
     * The event listener receives an App\Events\ApplicationEvent instance.
     *
     * @var string
     */
    const APPLICATION_SHUTDOWN = 'application.shutdown';

    /**
     * The NOTIFY_APPLICATION_STARTUP event occurs after the application
     * has been created and booted.
     *
     * The event listener receives an App\Events\ApplicationEvent instance.
     *
     * @var string
     */
    const APPLICATION_STARTUP = 'application.startup';

    # Errors
    const CORE_ERROR = 'core.error';

    # View
    const NOTIFY_RENDERING = 'view.rendering';

    # Composers
    const NOTIFY_COMPOSING = 'composing: ';
    const NOTIFY_COMPOSE   = 'compose';
    const NOTIFY_CREATE    = 'create';
    const NOTIFY_COMPOSERS = 'composing: compose'; # expects $name, $context
    const NOTIFY_CREATORS  = 'composing: create';

    # Routing
    const NOTIFY_ROUTING_XHR           = 'routing.xhr';
    const NOTIFY_ROUTING_REGISTERED    = 'routing.registered';
    const NOTIFY_ROUTING_FOUND         = 'routing.found';
    const NOTIFY_ROUTING_MATCH         = 'routing.match';
    const NOTIFY_ROUTING_FAIL          = 'routing.fail';
    const NOTIFY_REQUEST_LOADED        = 'request.loaded';
    const NOTIFY_BEFORE_ROUTE_DISPATCH = 'routing.dispatch.before';
    const NOTIFY_AFTER_ROUTE_DISPATCH  = 'routing.dispatch.after';

    # Auth
    const NOTIFY_IDENTITY_UPDATE      = 'identity.update';
    const NOTIFY_AUTHENTICATE_FAILURE = 'identity.authenticate.failure';
    const NOTIFY_AUTHENTICATE_SUCCESS = 'identity.authenticate.success';

    # Response
    const NOTIFY_RESPONSE_ERROR_HTTP    = 'respond.error_http';
    const NOTIFY_RESPONSE_ERROR_API     = 'respond.error_api';
    const NOTIFY_RESPONSE_ERROR_GENERAL = 'respond.error_general';
    const NOTIFY_RESPONSE_SEND_HTTP     = 'respond.http';
    const NOTIFY_RESPONSE_SENDING       = 'respond.send';

    # Database
    const PDO_BOOTED          = 'database.pdo_booted';
    const ORM_BOOTED          = 'database.orm_booted';
    const DATABASE_BOOTED     = 'database.booted';
    const DATABASE_QUERY      = 'database.query';
    const DATABASE_REGISTERED = 'database.registered';
    const MODELS_BOOTED       = 'database.models.booted';

}
