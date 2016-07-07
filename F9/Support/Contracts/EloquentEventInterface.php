<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
namespace F9\Contracts;

use Illuminate\Database\DatabaseManager;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
interface EloquentEventInterface
{
    public function __construct(DatabaseManager $database, array $payload = [], string $context = 'global.event', bool $halt = FALSE);

    /**
     * @return string
     */
    public function getContext();

    /**
     * @return DatabaseManager
     */
    public function getDatabase();

}
