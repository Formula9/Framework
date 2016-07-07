<?php namespace Nine\Database;

/**
 * F9 (Formula Nine) Personal PHP Framework
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
 */

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Nine\Containers\Forge;
use Nine\Exceptions\DBUnrecognizedMethodCall;

/**
 * DB is a convenience class that encapsulates method access to the
 * `Nine\Database` or `Illuminate\Database\Connection`.
 *
 * @method Builder table(string $table_name)
 * @method array select(string $query, array $bindings) - select('select * from users where id = ?', [2])
 * @method int update(string $query, array $bindings)
 * @method int delete(string $query, array $bindings)
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class DB
{
    const VERSION = '0.4.2';

    /** @var Connection - Illuminate Database Connection */
    private static $connection;

    /** @var Database - An instance of `Nine\Database` */
    private static $database;

    /** @var static - self reference for translating `static` to `method` calls. */
    private static $instance;

    /**
     * Database Facade
     *
     * The `DB` class expects, at the very least, that either the
     * `DatabaseServiceProvider` has been registered or both the
     * `F9\Database` and the `Illuminate\Database` have been properly
     * instantiated and registered with the `Forge` or `Application`.
     */
    public function __construct()
    {
        // we'll need to use the instance for non-static calls
        static::$instance = $this;

        // include only if the framework Database should be used.
        if (config('database.database_enabled')) {
            // get the current database object
            static::$database = Forge::find('Database');
        }

        // include only if eloquent should be used.
        if (config('database.eloquent_enabled')) {
            // get the current illuminate db connection
            static::$connection = Forge::find('db.connection');
        }
    }

    /**
     * Calls a method on a database connection.
     *
     * Note that the checking order determines which method will be called.
     * For example: `DB::update()` will return the illuminate update method.
     * To change this to return the Database::query_update method, simply
     * move the second test above the first.
     *
     * @param $method
     * @param $arguments
     *
     * @return mixed
     * @throws DBUnrecognizedMethodCall
     */
    public static function __callStatic($method, $arguments)
    {
        // instantiate if necessary
        static::$instance = static::$instance ?: new static();
        $properMethod = ucfirst($method);

        // look in eloquent if enabled.
        if (config('database.eloquent_enabled') and method_exists(static::$connection, $method)) {
            return call_user_func_array([static::$connection, $method], $arguments);
        }

        // second: try any query_* methods on the framework Database class
        //if (method_exists(static::$database, "query$properMethod")) {
        //    return call_user_func_array([static::$database, "query$properMethod"], $arguments);
        //}

        // third: try any non-query_* methods on the framework Database class
        if (method_exists(static::$database, $method)) {
            return call_user_func_array([static::$database, $method], $arguments);
        }

        // finally: fail if no method host can be found.
        throw new DBUnrecognizedMethodCall("Unrecognized method `$method` call in DB.");
    }

    /**
     * @param $connectionName
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public static function connection($connectionName) : ConnectionInterface
    {
        static::$instance = static::$instance ?: new static();
        $eloquent = Forge::find('db');

        return $eloquent->connection($connectionName);
    }

    /**
     * **Disable the query log.**
     */
    public static function disableQueryLog()
    {
        static::$instance = static::$instance ?: new static();

        static::$connection->disableQueryLog();
    }

    /**
     * **Enable the query log.**
     */
    public static function enableQueryLog()
    {
        static::$instance = static::$instance ?: new static();

        static::$connection->enableQueryLog();
    }

    /**
     * **Flush the query log.**
     */
    public static function flushQueryLog()
    {
        static::$instance = static::$instance ?: new static();

        static::$connection->flushQueryLog();
    }

    /**
     * Get the illuminate Connection used by `DB`.
     *
     * @return ConnectionInterface
     */
    public static function getConnection() : ConnectionInterface
    {
        static::$instance = static::$instance ?: new static();

        return self::$connection;
    }

    /**
     * @param string $connectionName
     *
     * @return DB|static
     */
    public static function setConnection($connectionName = 'default') : DB
    {
        static::$instance = static::$instance ?: new static();

        return static::using($connectionName);
    }

    /**
     * Get the F9\Database instance.
     *
     * @return DatabaseInterface
     */
    public static function getDatabase() : DatabaseInterface
    {
        static::$instance = static::$instance ?: new static();

        return self::$database;
    }

    /**
     * **Get the query log.**
     *
     * @return array
     */
    public static function getQueryLog() : array
    {
        static::$instance = static::$instance ?: new static();

        return static::$connection->getQueryLog();
    }

    /**
     * ** Fluently set the connection for subsequent method calls. **
     *
     * Warning: More than one connection is not supported and makes no sense.
     *
     * IE:
     *      DB::using(<connection_name>)::table...
     *      - or -
     *      DB::using(<connection_name>)->table...
     *
     * @param string $connectionName
     *
     * @return DB
     */
    public static function using($connectionName = 'default') : DB
    {
        static::$instance = static::$instance ?: new static();

        static::$connection = Forge::find('db')->connection($connectionName);

        return static::$instance;
    }

}
